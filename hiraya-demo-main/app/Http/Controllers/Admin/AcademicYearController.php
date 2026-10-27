<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\AcademicYearService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    public function index(): Response
    {
        $years = AcademicYear::query()
            ->withCount(['sections', 'classOfferings', 'enrollments'])
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (AcademicYear $year): array => [
                'id' => $year->id,
                'name' => $year->name,
                'start_date' => $year->start_date?->toDateString(),
                'end_date' => $year->end_date?->toDateString(),
                'enrollment_start' => $year->enrollment_start?->toDateString(),
                'enrollment_end' => $year->enrollment_end?->toDateString(),
                'status' => $year->status,
                'is_active' => $year->is_active,
                'enrollment_state' => $year->enrollmentState(),
                'sections_count' => $year->sections_count,
                'offerings_count' => $year->class_offerings_count,
                'enrollments_count' => $year->enrollments_count,
            ]);

        return Inertia::render('Admin/AcademicYears/Index', [
            'years' => $years,
        ]);
    }

    public function store(Request $request, AcademicYearService $academicYearService): RedirectResponse
    {
        $validated = $this->validateYear($request);
        $year = AcademicYear::create([
            ...$validated,
            'is_active' => false,
        ]);

        AuditLogger::log('academic_year.created', 'academic_years', $year->id, null, $validated);

        if ($validated['status'] === 'active') {
            $academicYearService->activate($year, $request->user());
        }

        return back()->with('success', 'Academic year created.');
    }

    public function update(Request $request, AcademicYear $academicYear, AcademicYearService $academicYearService): RedirectResponse
    {
        $validated = $this->validateYear($request, $academicYear);
        $oldValues = $academicYear->only(['name', 'start_date', 'end_date', 'enrollment_start', 'enrollment_end', 'status']);

        $academicYear->update($validated);

        AuditLogger::log('academic_year.updated', 'academic_years', $academicYear->id, $oldValues, $validated);

        if ($validated['status'] === 'active') {
            $academicYearService->activate($academicYear, $request->user());
        }

        return back()->with('success', 'Academic year updated.');
    }

    public function activate(Request $request, AcademicYear $academicYear, AcademicYearService $academicYearService): RedirectResponse
    {
        $academicYearService->activate($academicYear, $request->user());

        return back()->with('success', "{$academicYear->name} is now the active academic year.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateYear(Request $request, ?AcademicYear $academicYear = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^SY\s\d{4}-\d{4}$/',
                'unique:academic_years,name'.($academicYear ? ",{$academicYear->id}" : ''),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'enrollment_start' => ['required', 'date'],
            'enrollment_end' => ['required', 'date', 'after_or_equal:enrollment_start'],
            'status' => ['required', 'in:upcoming,active,completed'],
        ]);
    }
}
