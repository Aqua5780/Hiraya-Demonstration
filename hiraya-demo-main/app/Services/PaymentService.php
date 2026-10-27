<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * Submit a payment for finance verification.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function submit(Student $student, AcademicYear $year, array $data, User $actor): Payment
    {
        $amount = round((float) $data['amount'], 2);
        $this->assertValidAmount($student, $year, $amount);

        $payment = Payment::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'amount' => $amount,
            'method' => $data['method'],
            'reference_number' => $data['reference_number'],
            'proof_path' => $data['proof_path'] ?? null,
            'proof_name' => $data['proof_name'] ?? null,
            'proof_mime' => $data['proof_mime'] ?? null,
            'proof_size' => $data['proof_size'] ?? null,
            'status' => Payment::PENDING,
            'submitted_by' => $actor->id,
        ]);

        AuditLogger::log(
            'payment.submitted',
            'payments',
            $payment->id,
            null,
            [
                'student_id' => $student->id,
                'amount' => $amount,
                'method' => $payment->method,
                'reference_number' => $payment->reference_number,
            ],
            $actor,
        );

        return $payment;
    }

    /**
     * Verify a pending payment and post it to the student's ledger.
     *
     * @throws ValidationException
     */
    public function verify(Payment $payment, User $verifier, ?string $remarks = null): Payment
    {
        if ($payment->status !== Payment::PENDING) {
            throw ValidationException::withMessages([
                'payment' => 'Only pending payments can be verified.',
            ]);
        }

        return DB::transaction(function () use ($payment, $verifier, $remarks): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Payment::PENDING) {
                throw ValidationException::withMessages([
                    'payment' => 'Only pending payments can be verified.',
                ]);
            }

            $this->assertValidAmount($locked->student, $locked->academicYear, (float) $locked->amount);

            $locked->forceFill([
                'status' => Payment::VERIFIED,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
                'remarks' => $remarks,
            ])->save();

            $locked->forceFill([
                'receipt_number' => $this->nextReceiptNumber($locked),
            ])->save();

            $balance = $this->postLedgerEntry($locked, $verifier);

            AuditLogger::log(
                'payment.verified',
                'payments',
                $locked->id,
                ['status' => Payment::PENDING],
                [
                    'status' => Payment::VERIFIED,
                    'amount' => (float) $locked->amount,
                    'receipt_number' => $locked->receipt_number,
                    'balance_after' => $balance,
                ],
                $verifier,
            );

            return $locked->fresh(['student', 'academicYear', 'verifiedBy']);
        });
    }

    /**
     * Reject a pending payment.
     */
    public function reject(Payment $payment, User $verifier, string $remarks): Payment
    {
        if ($payment->status !== Payment::PENDING) {
            throw ValidationException::withMessages([
                'payment' => 'Only pending payments can be rejected.',
            ]);
        }

        return DB::transaction(function () use ($payment, $verifier, $remarks): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Payment::PENDING) {
                throw ValidationException::withMessages([
                    'payment' => 'Only pending payments can be rejected.',
                ]);
            }

            $locked->forceFill([
                'status' => Payment::REJECTED,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
                'remarks' => $remarks,
            ])->save();

            AuditLogger::log(
                'payment.rejected',
                'payments',
                $locked->id,
                ['status' => Payment::PENDING],
                ['status' => Payment::REJECTED, 'remarks' => $remarks],
                $verifier,
            );

            return $locked->fresh();
        });
    }

    /**
     * Post a walk-in payment directly from the finance dashboard.
     *
     * @param  array<string, mixed>  $data
     */
    public function postDirect(Student $student, AcademicYear $year, array $data, User $actor): Payment
    {
        $amount = round((float) $data['amount'], 2);
        $this->assertValidAmount($student, $year, $amount);

        $payment = Payment::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'amount' => $amount,
            'method' => $data['method'],
            'reference_number' => $data['reference_number'] ?? 'WALK-IN-'.now()->format('YmdHis'),
            'status' => Payment::PENDING,
            'submitted_by' => $actor->id,
        ]);

        return $this->verify($payment, $actor, $data['remarks'] ?? 'Posted at the finance office.');
    }

    public function balanceFor(Student $student, AcademicYear $year): float
    {
        $totals = LedgerEntry::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $year->id)
            ->selectRaw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total')
            ->first();

        return round((float) ($totals?->debit_total ?? 0) - (float) ($totals?->credit_total ?? 0), 2);
    }

    /**
     * @throws ValidationException
     */
    private function assertValidAmount(Student $student, AcademicYear $year, float $amount): void
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'The payment amount must be greater than zero.',
            ]);
        }

        $balance = $this->balanceFor($student, $year);

        if ($balance <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'This student has no outstanding balance for the selected academic year.',
            ]);
        }

        if ($amount > $balance) {
            throw ValidationException::withMessages([
                'amount' => 'The payment amount cannot exceed the remaining balance of ₱'.number_format($balance, 2).'.',
            ]);
        }
    }

    private function postLedgerEntry(Payment $payment, User $actor): float
    {
        $balance = $this->balanceFor($payment->student, $payment->academicYear) - (float) $payment->amount;
        $balance = max(0, round($balance, 2));

        LedgerEntry::create([
            'student_id' => $payment->student_id,
            'academic_year_id' => $payment->academic_year_id,
            'payment_id' => $payment->id,
            'type' => 'payment',
            'description' => "Payment {$payment->receipt_number}",
            'debit' => 0,
            'credit' => $payment->amount,
            'balance_after' => $balance,
            'created_by' => $actor->id,
        ]);

        Enrollment::query()
            ->where('student_id', $payment->student_id)
            ->where('academic_year_id', $payment->academic_year_id)
            ->update(['balance' => $balance]);

        return $balance;
    }

    private function nextReceiptNumber(Payment $payment): string
    {
        return sprintf('OR-%s-%05d', now()->format('Y'), $payment->id);
    }
}
