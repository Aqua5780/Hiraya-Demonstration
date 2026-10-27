<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Student;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request, PaymentService $paymentService): Response
    {
        $year = AcademicYear::query()->where('is_active', true)->first();
        $filters = $request->only(['q']) + ['q' => ''];

        $students = $year
            ? Student::query()
                ->with(['program:id,code', 'enrollments' => fn ($query) => $query
                    ->where('academic_year_id', $year->id)
                    ->with('section:id,name')])
                ->whereHas('enrollments', fn ($query) => $query
                    ->where('academic_year_id', $year->id)
                    ->where('balance', '>', 0))
                ->when($filters['q'] !== '', function ($query) use ($filters): void {
                    $query->where(function ($query) use ($filters): void {
                        $query->where('student_number', 'like', "%{$filters['q']}%")
                            ->orWhere('first_name', 'like', "%{$filters['q']}%")
                            ->orWhere('last_name', 'like', "%{$filters['q']}%");
                    });
                })
                ->orderBy('last_name')
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Student $student): array => [
                    'id' => $student->id,
                    'student_number' => $student->student_number,
                    'name' => $student->full_name,
                    'program' => $student->program?->code,
                    'section' => $student->enrollments->first()?->section?->name,
                    'assessed_amount' => (float) ($student->enrollments->first()?->assessed_amount ?? 0),
                    'balance' => (float) ($student->enrollments->first()?->balance ?? 0),
                ])
            : null;

        return Inertia::render('Finance/Accounts/Index', [
            'year' => $year?->only(['id', 'name']),
            'students' => $students,
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, Student $student, PaymentService $paymentService): Response
    {
        $year = AcademicYear::query()->where('is_active', true)->first();
        $student->load(['program:id,code,name', 'yearLevel:id,name', 'user:id,email,status']);

        $enrollment = $year
            ? $student->enrollments()
                ->where('academic_year_id', $year->id)
                ->with('section:id,name,program_id,year_level_id')
                ->first()
            : null;

        $balance = $year ? $paymentService->balanceFor($student, $year) : 0;

        return Inertia::render('Finance/Accounts/Show', [
            'year' => $year?->only(['id', 'name']),
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'name' => $student->full_name,
                'program' => $student->program?->code,
                'year_level' => $student->yearLevel?->name,
                'email' => $student->email,
                'account_status' => $student->user?->status,
            ],
            'enrollment' => $enrollment ? [
                'section' => $enrollment->section?->name,
                'units' => (float) $enrollment->total_units,
                'assessed_amount' => (float) $enrollment->assessed_amount,
                'balance' => $balance,
            ] : null,
            'ledger' => $year
                ? LedgerEntry::query()
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $year->id)
                    ->latest()
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
                : collect(),
            'payments' => $year
                ? Payment::query()
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $year->id)
                    ->latest()
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
                    ])
                : collect(),
        ]);
    }
}
