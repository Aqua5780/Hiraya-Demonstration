<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassOffering;
use App\Models\EnrollmentDetail;
use App\Services\GradeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    public function index(Request $request): Response
    {
        $faculty = $request->user()->facultyProfile;
        abort_if(! $faculty, 403, 'Your faculty profile is not configured.');

        $year = AcademicYear::query()->where('is_active', true)->first();

        $classes = ClassOffering::query()
            ->with(['subject:id,code,name', 'section:id,name,program_id,year_level_id', 'section.program:id,code', 'section.yearLevel:id,name'])
            ->withCount([
                'enrollmentDetails as enrolled_count' => fn ($query) => $query->where('status', 'enrolled'),
                'enrollmentDetails as graded_count' => fn ($query) => $query->where('status', 'enrolled')->whereHas('grade'),
            ])
            ->where('faculty_id', $faculty->id)
            ->when($year, fn ($query) => $query->where('academic_year_id', $year->id))
            ->get()
            ->map(fn (ClassOffering $offering): array => [
                'id' => $offering->id,
                'subject' => $offering->subject?->code,
                'subject_name' => $offering->subject?->name,
                'section' => $offering->section?->name,
                'program' => $offering->section?->program?->code,
                'year_level' => $offering->section?->yearLevel?->name,
                'students' => $offering->enrolled_count,
                'graded' => $offering->graded_count,
                'pending' => max(0, $offering->enrolled_count - $offering->graded_count),
            ]);

        return Inertia::render('Faculty/Grades/Index', [
            'year' => $year?->only(['id', 'name']),
            'classes' => $classes,
        ]);
    }

    public function show(Request $request, ClassOffering $classOffering): Response
    {
        $faculty = $request->user()->facultyProfile;
        abort_if(! $faculty, 403, 'Your faculty profile is not configured.');
        abort_unless((int) $classOffering->faculty_id === (int) $faculty->id, 403, 'You can only encode grades for your own classes.');

        $classOffering->load([
            'academicYear:id,name',
            'subject:id,code,name,units',
            'section:id,name,program_id,year_level_id',
            'section.program:id,code',
            'section.yearLevel:id,name',
            'enrollmentDetails' => fn ($query) => $query
                ->where('status', 'enrolled')
                ->with(['enrollment.student:id,student_number,first_name,middle_name,last_name,suffix', 'grade']),
        ]);

        return Inertia::render('Faculty/Grades/Show', [
            'classOffering' => [
                'id' => $classOffering->id,
                'subject' => $classOffering->subject?->code,
                'subject_name' => $classOffering->subject?->name,
                'section' => $classOffering->section?->name,
                'program' => $classOffering->section?->program?->code,
                'year_level' => $classOffering->section?->yearLevel?->name,
                'academic_year' => $classOffering->academicYear?->name,
                'passing_grade' => (float) config('hiraya.grades.passing_grade'),
                'midterm_weight' => (float) config('hiraya.grades.midterm_weight'),
                'final_weight' => (float) config('hiraya.grades.final_weight'),
            ],
            'students' => $classOffering->enrollmentDetails->map(fn ($detail): array => [
                'detail_id' => $detail->id,
                'student_number' => $detail->enrollment?->student?->student_number,
                'name' => $detail->enrollment?->student?->full_name,
                'midterm' => $detail->grade?->midterm !== null ? (float) $detail->grade->midterm : null,
                'final' => $detail->grade?->final !== null ? (float) $detail->grade->final : null,
                'final_rating' => $detail->grade?->final_rating !== null ? (float) $detail->grade->final_rating : null,
                'remarks' => $detail->grade?->remarks,
            ])->values(),
        ]);
    }

    public function store(Request $request, ClassOffering $classOffering, GradeService $gradeService): RedirectResponse
    {
        $faculty = $request->user()->facultyProfile;
        abort_if(! $faculty, 403, 'Your faculty profile is not configured.');
        abort_unless((int) $classOffering->faculty_id === (int) $faculty->id, 403, 'You can only encode grades for your own classes.');

        $validated = $request->validate([
            'grades' => ['required', 'array'],
            'grades.*.enrollment_detail_id' => ['required', 'integer', 'exists:enrollment_details,id'],
            'grades.*.midterm' => ['nullable', 'numeric', 'between:0,100'],
            'grades.*.final' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $details = EnrollmentDetail::query()
            ->where('class_offering_id', $classOffering->id)
            ->whereIn('id', collect($validated['grades'])->pluck('enrollment_detail_id'))
            ->get()
            ->keyBy('id');

        foreach ($validated['grades'] as $entry) {
            $detail = $details->get($entry['enrollment_detail_id']);

            if (! $detail) {
                throw ValidationException::withMessages([
                    'grades' => 'One or more students do not belong to this class.',
                ]);
            }

            $gradeService->save($detail, [
                'midterm' => $entry['midterm'] ?? null,
                'final' => $entry['final'] ?? null,
            ], $faculty, $request->user());
        }

        return back()->with('success', 'Grades saved.');
    }
}
