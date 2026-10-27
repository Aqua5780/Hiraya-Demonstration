<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Payment;
use App\Models\Student;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'q']) + [
            'status' => 'pending',
            'q' => '',
        ];

        $payments = Payment::query()
            ->with(['student:id,student_number,first_name,middle_name,last_name,suffix', 'academicYear:id,name', 'verifiedBy:id,name'])
            ->when($filters['status'] !== 'all', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $query->whereHas('student', function ($query) use ($filters): void {
                    $query->where('student_number', 'like', "%{$filters['q']}%")
                        ->orWhere('first_name', 'like', "%{$filters['q']}%")
                        ->orWhere('last_name', 'like', "%{$filters['q']}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Payment $payment): array => [
                'id' => $payment->id,
                'student_number' => $payment->student?->student_number,
                'student_name' => $payment->student?->full_name,
                'amount' => (float) $payment->amount,
                'method' => $payment->methodLabel(),
                'reference_number' => $payment->reference_number,
                'status' => $payment->status,
                'proof_name' => $payment->proof_name,
                'remarks' => $payment->remarks,
                'receipt_number' => $payment->receipt_number,
                'created_at' => $payment->created_at?->toIso8601String(),
                'verified_at' => $payment->verified_at?->toIso8601String(),
                'verified_by' => $payment->verifiedBy?->name,
                'year' => $payment->academicYear?->name,
            ]);

        return Inertia::render('Finance/Payments/Index', [
            'payments' => $payments,
            'filters' => $filters,
            'pendingCount' => Payment::query()->where('status', Payment::PENDING)->count(),
        ]);
    }

    public function verify(Request $request, Payment $payment, PaymentService $paymentService): RedirectResponse
    {
        $verified = $paymentService->verify($payment, $request->user());

        return back()->with('success', "Payment {$verified->receipt_number} verified and posted.");
    }

    public function reject(Request $request, Payment $payment, PaymentService $paymentService): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:500'],
        ]);

        $paymentService->reject($payment, $request->user(), $validated['remarks']);

        return back()->with('success', 'Payment rejected.');
    }

    public function create(Request $request, PaymentService $paymentService): Response
    {
        $year = AcademicYear::query()->where('is_active', true)->first();
        $filters = $request->only(['q', 'student_id']) + ['q' => '', 'student_id' => ''];

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
                ->take(12)
                ->get()
                ->map(fn (Student $student): array => [
                    'id' => $student->id,
                    'student_number' => $student->student_number,
                    'name' => $student->full_name,
                    'program' => $student->program?->code,
                    'section' => $student->enrollments->first()?->section?->name,
                    'balance' => (float) ($student->enrollments->first()?->balance ?? 0),
                ])
            : collect();

        $selectedStudent = null;

        if ($filters['student_id'] !== '' && $year) {
            $student = Student::query()
                ->with(['program:id,code', 'enrollments' => fn ($query) => $query
                    ->where('academic_year_id', $year->id)
                    ->with('section:id,name')])
                ->find($filters['student_id']);

            if ($student && $student->enrollments->isNotEmpty()) {
                $selectedStudent = [
                    'id' => $student->id,
                    'student_number' => $student->student_number,
                    'name' => $student->full_name,
                    'program' => $student->program?->code,
                    'section' => $student->enrollments->first()?->section?->name,
                    'balance' => $paymentService->balanceFor($student, $year),
                ];
            }
        }

        return Inertia::render('Finance/Payments/Create', [
            'year' => $year?->only(['id', 'name']),
            'students' => $students,
            'selectedStudent' => $selectedStudent,
            'filters' => $filters,
            'methods' => collect(config('hiraya.payments.methods'))->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])->values(),
        ]);
    }

    public function store(Request $request, PaymentService $paymentService): RedirectResponse
    {
        $year = AcademicYear::query()->where('is_active', true)->first();
        abort_if(! $year, 422, 'No academic year is active.');

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:'.implode(',', array_keys(config('hiraya.payments.methods')))],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $student = Student::query()->findOrFail($validated['student_id']);

        $payment = $paymentService->postDirect($student, $year, [
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'reference_number' => $validated['reference_number'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
        ], $request->user());

        return redirect()->route('payments.receipt', $payment)->with('success', 'Payment posted.');
    }
}
