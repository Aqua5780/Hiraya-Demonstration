<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Services\AttachmentService;
use App\Services\EnrollmentService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request, PaymentService $paymentService, EnrollmentService $enrollmentService): Response
    {
        $student = $request->user()->student;
        abort_if(! $student, 403, 'Your student profile is not configured.');

        $year = AcademicYear::query()->where('is_active', true)->first();
        $enrollment = $year ? $enrollmentService->currentEnrollment($student, $year) : null;
        $balance = $year ? $paymentService->balanceFor($student, $year) : 0;

        $payments = $year
            ? Payment::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $year->id)
                ->latest()
                ->take(10)
                ->get()
                ->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'method' => $payment->methodLabel(),
                    'reference_number' => $payment->reference_number,
                    'status' => $payment->status,
                    'receipt_number' => $payment->receipt_number,
                    'proof_name' => $payment->proof_name,
                    'remarks' => $payment->remarks,
                    'created_at' => $payment->created_at?->toIso8601String(),
                    'verified_at' => $payment->verified_at?->toIso8601String(),
                ])
            : collect();

        $ledger = $year
            ? LedgerEntry::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $year->id)
                ->latest()
                ->take(10)
                ->get()
                ->map(fn (LedgerEntry $entry): array => [
                    'id' => $entry->id,
                    'type' => $entry->type,
                    'description' => $entry->description,
                    'debit' => (float) $entry->debit,
                    'credit' => (float) $entry->credit,
                    'balance_after' => (float) $entry->balance_after,
                    'created_at' => $entry->created_at?->toIso8601String(),
                ])
            : collect();

        return Inertia::render('Student/Payments/Index', [
            'year' => $year?->only(['id', 'name']),
            'enrollment' => $enrollment ? [
                'section' => $enrollment->section?->displayName(),
                'assessed_amount' => (float) $enrollment->assessed_amount,
                'balance' => (float) $enrollment->balance,
            ] : null,
            'balance' => $balance,
            'payments' => $payments,
            'ledger' => $ledger,
            'methods' => collect(config('hiraya.payments.methods'))->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])->values(),
        ]);
    }

    public function store(Request $request, PaymentService $paymentService, AttachmentService $attachmentService): RedirectResponse
    {
        $student = $request->user()->student;
        abort_if(! $student, 403, 'Your student profile is not configured.');

        $year = AcademicYear::query()->where('is_active', true)->first();
        abort_if(! $year, 422, 'No academic year is active.');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:'.implode(',', array_keys(config('hiraya.payments.methods')))],
            'reference_number' => ['required', 'string', 'max:100'],
            'proof' => [
                'required',
                'file',
                'max:'.config('hiraya.uploads.payment_proof_max_kb'),
                'mimes:'.implode(',', config('hiraya.uploads.payment_proof_mimes')),
            ],
        ]);

        $proof = $attachmentService->store($request->file('proof'), 'payment-proofs');

        $paymentService->submit($student, $year, [
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'reference_number' => $validated['reference_number'],
            'proof_path' => $proof['path'],
            'proof_name' => $proof['name'],
            'proof_mime' => $proof['mime'],
            'proof_size' => $proof['size'],
        ], $request->user());

        return back()->with('success', 'Payment submitted for finance verification.');
    }
}
