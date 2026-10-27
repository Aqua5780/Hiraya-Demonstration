<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassOffering;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    public function index(Request $request, EnrollmentService $enrollmentService): Response
    {
        $student = $request->user()->student;
        abort_if(! $student, 403, 'Your student profile is not configured.');

        $year = AcademicYear::query()->where('is_active', true)->first();
        $enrollment = $year ? $enrollmentService->currentEnrollment($student, $year) : null;
        $offerings = $year ? $enrollmentService->eligibleOfferings($student, $year) : collect();

        return Inertia::render('Student/Enrollment/Index', [
            'year' => $year?->only(['id', 'name', 'enrollment_start', 'enrollment_end', 'status']),
            'enrollmentState' => $year?->enrollmentState(),
            'canEnroll' => $year?->isEnrollmentOpen() === true && $enrollment === null,
            'assessment' => $year ? $enrollmentService->assessmentFor($student, $year) : 0,
            'balance' => $year ? $enrollmentService->balanceFor($student, $year) : 0,
            'offerings' => $offerings->map(fn (ClassOffering $offering): array => [
                'id' => $offering->id,
                'subject' => $offering->subject?->code,
                'subject_name' => $offering->subject?->name,
                'units' => (float) ($offering->subject?->units ?? 0),
                'section_id' => $offering->section_id,
                'section' => $offering->section?->name,
                'program' => $offering->section?->program?->code,
                'year_level' => $offering->section?->yearLevel?->name,
                'faculty' => $offering->faculty?->user?->name,
                'room' => $offering->room,
                'capacity' => $offering->capacity,
                'enrolled' => $offering->enrolledCount(),
                'available' => $offering->availableSlots(),
                'schedules' => $offering->schedules->map(fn ($schedule): array => [
                    'id' => $schedule->id,
                    'day_of_week' => $schedule->day_of_week,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'room' => $schedule->room,
                    'label' => $schedule->label(),
                ])->values(),
            ])->values(),
            'enrollment' => $enrollment ? [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'section' => $enrollment->section?->displayName(),
                'units' => (float) $enrollment->total_units,
                'assessed_amount' => (float) $enrollment->assessed_amount,
                'balance' => (float) $enrollment->balance,
                'classes' => $enrollment->details->map(fn ($detail): array => [
                    'id' => $detail->id,
                    'subject' => $detail->classOffering?->subject?->code,
                    'subject_name' => $detail->classOffering?->subject?->name,
                    'units' => (float) ($detail->classOffering?->subject?->units ?? 0),
                    'schedule' => $detail->classOffering?->scheduleLabel(),
                    'room' => $detail->classOffering?->room,
                    'grade' => $detail->grade?->final_rating !== null ? (float) $detail->grade->final_rating : null,
                ])->values(),
            ] : null,
        ]);
    }

    public function store(Request $request, EnrollmentService $enrollmentService): RedirectResponse
    {
        $student = $request->user()->student;
        abort_if(! $student, 403, 'Your student profile is not configured.');

        $year = AcademicYear::query()->where('is_active', true)->first();
        abort_if(! $year, 422, 'No academic year is active.');

        $validated = $request->validate([
            'classes' => ['required', 'array', 'min:1'],
            'classes.*' => ['integer', 'exists:class_offerings,id'],
        ]);

        $enrollmentService->enroll($student, $year, $validated['classes'], $request->user());

        return redirect()->route('student.enrollment.index')->with('success', 'Enrollment submitted.');
    }
}
