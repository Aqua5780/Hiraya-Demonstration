<?php

namespace App\Features\Reversal\Services;

use App\Features\Reversal\Models\PaymentReversal;
use App\Models\Enrollment;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentReversalService
{
    public function __construct(private readonly PaymentService $paymentService) {}

    /**
     * Request a reversal for a verified payment.
     */
    public function request(Payment $payment, User $actor, string $reason): PaymentReversal
    {
        if ($payment->status !== Payment::VERIFIED) {
            throw ValidationException::withMessages([
                'payment' => 'Only verified payments can be reversed.',
            ]);
        }

        if (PaymentReversal::query()->where('payment_id', $payment->id)->where('status', PaymentReversal::APPROVED)->exists()) {
            throw ValidationException::withMessages([
                'payment' => 'This payment has already been reversed.',
            ]);
        }

        $reversal = PaymentReversal::create([
            'payment_id' => $payment->id,
            'reversed_by' => $actor->id,
            'amount' => $payment->amount,
            'reason' => $reason,
            'status' => PaymentReversal::PENDING,
        ]);

        AuditLogger::log('payment.reversal_requested', 'payment_reversals', $reversal->id, null, [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
        ], $actor);

        return $reversal;
    }

    /**
     * Approve a reversal and post the balancing ledger entry.
     */
    public function approve(PaymentReversal $reversal, User $approver, ?string $reference = null): PaymentReversal
    {
        if ($reversal->status !== PaymentReversal::PENDING) {
            throw ValidationException::withMessages([
                'reversal' => 'Only pending reversals can be approved.',
            ]);
        }

        return DB::transaction(function () use ($reversal, $approver, $reference): PaymentReversal {
            $payment = $reversal->payment;
            $balance = $this->paymentService->balanceFor($payment->student, $payment->academicYear);
            $newBalance = round($balance + (float) $reversal->amount, 2);

            $reversal->forceFill([
                'status' => PaymentReversal::APPROVED,
                'approved_by' => $approver->id,
                'reference' => $reference,
            ])->save();

            LedgerEntry::create([
                'student_id' => $payment->student_id,
                'academic_year_id' => $payment->academic_year_id,
                'payment_id' => $payment->id,
                'type' => 'reversal',
                'description' => 'Reversal of receipt '.$payment->receipt_number,
                'debit' => $reversal->amount,
                'credit' => 0,
                'balance_after' => $newBalance,
                'created_by' => $approver->id,
            ]);

            Enrollment::query()
                ->where('student_id', $payment->student_id)
                ->where('academic_year_id', $payment->academic_year_id)
                ->update(['balance' => $newBalance]);

            AuditLogger::log('payment.reversal_approved', 'payment_reversals', $reversal->id, null, [
                'payment_id' => $payment->id,
                'balance_after' => $newBalance,
            ], $approver);

            return $reversal->fresh(['payment.student', 'approvedBy']);
        });
    }

    public function reject(PaymentReversal $reversal, User $approver, string $remarks): PaymentReversal
    {
        if ($reversal->status !== PaymentReversal::PENDING) {
            throw ValidationException::withMessages([
                'reversal' => 'Only pending reversals can be rejected.',
            ]);
        }

        $reversal->forceFill([
            'status' => PaymentReversal::REJECTED,
            'approved_by' => $approver->id,
            'reason' => $reversal->reason."\nRejection: {$remarks}",
        ])->save();

        AuditLogger::log('payment.reversal_rejected', 'payment_reversals', $reversal->id, null, [
            'remarks' => $remarks,
        ], $approver);

        return $reversal->fresh();
    }
}
