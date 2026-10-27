<?php

namespace App\Features\Payroll\Http\Controllers;

use App\Features\Payroll\Models\CompensationRule;
use App\Features\Payroll\Models\PayPeriod;
use App\Features\Payroll\Models\Payroll;
use App\Features\Payroll\Services\PayrollService;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FacultyProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Deferred/Payroll/Index', [
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'faculty' => FacultyProfile::query()->with('user:id,name')->orderBy('employee_number')->get()->map(fn (FacultyProfile $faculty): array => [
                'id' => $faculty->id,
                'name' => $faculty->user?->name,
                'employee_number' => $faculty->employee_number,
            ]),
            'periods' => PayPeriod::query()
                ->with(['academicYear:id,name', 'payrolls' => fn ($query) => $query->with(['preparedBy:id,name', 'approvedBy:id,name'])->withCount('items')])
                ->latest('start_date')
                ->get(),
            'rules' => CompensationRule::query()->with('faculty.user:id,name')->latest()->get(),
        ]);
    }

    public function show(Payroll $payroll): Response
    {
        $payroll->load(['payPeriod.academicYear', 'preparedBy:id,name', 'approvedBy:id,name', 'items.faculty.user:id,name']);

        return Inertia::render('Deferred/Payroll/Show', [
            'payroll' => $payroll,
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:open,closed'],
        ]);

        $period = PayPeriod::create($validated);

        return back()->with('success', "Pay period {$period->name} created.");
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'faculty_id' => ['nullable', 'exists:faculty_profiles,id'],
            'name' => ['required', 'string', 'max:100'],
            'basis' => ['required', 'in:hourly,monthly'],
            'rate' => ['required', 'numeric', 'min:0'],
            'allowance' => ['required', 'numeric', 'min:0'],
            'deduction' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $rule = CompensationRule::create($validated);

        return back()->with('success', "Compensation rule {$rule->name} created.");
    }

    public function generate(Request $request, PayPeriod $payPeriod, PayrollService $payrollService): RedirectResponse
    {
        $payroll = $payrollService->generateDraft($payPeriod, $request->user());

        return redirect()->route('finance.payroll.show', $payroll)->with('success', 'Payroll draft generated.');
    }

    public function submit(Request $request, Payroll $payroll, PayrollService $payrollService): RedirectResponse
    {
        $payrollService->submit($payroll, $request->user());

        return back()->with('success', 'Payroll submitted for approval.');
    }

    public function approve(Request $request, Payroll $payroll, PayrollService $payrollService): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $payrollService->approve($payroll, $request->user(), $validated['remarks'] ?? null);

        return back()->with('success', 'Payroll approved.');
    }

    public function returnToPreparer(Request $request, Payroll $payroll, PayrollService $payrollService): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:500'],
        ]);

        $payrollService->returnToPreparer($payroll, $request->user(), $validated['remarks']);

        return back()->with('success', 'Payroll returned to the preparer.');
    }

    public function post(Request $request, Payroll $payroll, PayrollService $payrollService): RedirectResponse
    {
        $payrollService->post($payroll, $request->user());

        return back()->with('success', 'Payroll posted.');
    }
}
