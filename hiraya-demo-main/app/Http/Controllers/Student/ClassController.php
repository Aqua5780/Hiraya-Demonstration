<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassOffering;
use App\Models\EnrollmentDetail;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassController extends Controller
{
    public function index(Request $request, EnrollmentService $enrollmentService): Response
    {
        $student = $request->user()->student;
        abort_if(! $student, 403, 'Your student profile is not configured.');

        $year = AcademicYear::query()->where('is_active', true)->first();
        $enrollment = $year ? $enrollmentService->currentEnrollment($student, $year) : null;

        $classes = $enrollment
            ? $enrollment->details->map(fn ($detail): array => [
                'id' => $detail->id,
                'offering_id' => $detail->class_offering_id,
                'subject' => $detail->classOffering?->subject?->code,
                'subject_name' => $detail->classOffering?->subject?->name,
                'units' => (float) ($detail->classOffering?->subject?->units ?? 0),
                'section' => $detail->classOffering?->section?->name,
                'faculty' => $detail->classOffering?->faculty?->user?->name,
                'schedule' => $detail->classOffering?->scheduleLabel(),
                'room' => $detail->classOffering?->room,
                'grade' => $detail->grade?->final_rating !== null ? (float) $detail->grade->final_rating : null,
                'remarks' => $detail->grade?->remarks,
            ])->values()
            : collect();

        return Inertia::render('Student/Classes/Index', [
            'year' => $year?->only(['id', 'name']),
            'enrollment' => $enrollment ? [
                'section' => $enrollment->section?->displayName(),
                'units' => (float) $enrollment->total_units,
            ] : null,
            'classes' => $classes,
        ]);
    }

    public function show(Request $request, ClassOffering $classOffering): Response
    {
        $student = $request->user()->student;
        abort_if(! $student, 403, 'Your student profile is not configured.');

        $year = AcademicYear::query()->where('is_active', true)->first();
        abort_if(! $year || $classOffering->academic_year_id !== $year->id, 404);

        $enrolled = EnrollmentDetail::query()
            ->where('class_offering_id', $classOffering->id)
            ->where('status', 'enrolled')
            ->whereHas('enrollment', fn ($query) => $query
                ->where('student_id', $student->id)
                ->where('academic_year_id', $year->id)
                ->where('status', 'enrolled'))
            ->exists();

        abort_unless($enrolled, 403, 'You are not enrolled in this class.');

        $classOffering->load([
            'subject:id,code,name,units',
            'section:id,name,program_id,year_level_id',
            'section.program:id,code',
            'section.yearLevel:id,name',
            'faculty:id,user_id,employee_number',
            'faculty.user:id,name',
            'schedules',
            'enrollmentDetails' => fn ($query) => $query
                ->where('status', 'enrolled')
                ->with(['enrollment.student:id,student_number,first_name,middle_name,last_name,suffix']),
        ]);

        return Inertia::render('Student/Classes/Show', [
            'classOffering' => [
                'id' => $classOffering->id,
                'subject' => $classOffering->subject?->code,
                'subject_name' => $classOffering->subject?->name,
                'units' => (float) ($classOffering->subject?->units ?? 0),
                'section' => $classOffering->section?->name,
                'program' => $classOffering->section?->program?->code,
                'year_level' => $classOffering->section?->yearLevel?->name,
                'faculty' => $classOffering->faculty?->user?->name,
                'schedule' => $classOffering->scheduleLabel(),
                'room' => $classOffering->room,
            ],
            'roster' => $classOffering->enrollmentDetails->map(fn ($detail): array => [
                'id' => $detail->id,
                'student_number' => $detail->enrollment?->student?->student_number,
                'name' => $detail->enrollment?->student?->full_name,
            ])->values(),
        ]);
    }
}
