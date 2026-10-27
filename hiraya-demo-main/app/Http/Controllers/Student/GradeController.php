<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    public function __invoke(Request $request, EnrollmentService $enrollmentService): Response
    {
        $student = $request->user()->student;
        abort_if(! $student, 403, 'Your student profile is not configured.');

        $year = AcademicYear::query()->where('is_active', true)->first();
        $enrollment = $year ? $enrollmentService->currentEnrollment($student, $year) : null;

        $grades = $enrollment
            ? $enrollment->details->map(fn ($detail): array => [
                'id' => $detail->id,
                'subject' => $detail->classOffering?->subject?->code,
                'subject_name' => $detail->classOffering?->subject?->name,
                'units' => (float) ($detail->classOffering?->subject?->units ?? 0),
                'faculty' => $detail->classOffering?->faculty?->user?->name,
                'midterm' => $detail->grade?->midterm !== null ? (float) $detail->grade->midterm : null,
                'final' => $detail->grade?->final !== null ? (float) $detail->grade->final : null,
                'final_rating' => $detail->grade?->final_rating !== null ? (float) $detail->grade->final_rating : null,
                'remarks' => $detail->grade?->remarks,
            ])->values()
            : collect();

        $graded = $grades->filter(fn (array $grade): bool => $grade['final_rating'] !== null);
        $average = $graded->isNotEmpty()
            ? round($graded->avg('final_rating'), 2)
            : null;

        return Inertia::render('Student/Grades/Index', [
            'year' => $year?->only(['id', 'name']),
            'enrollment' => $enrollment ? [
                'section' => $enrollment->section?->displayName(),
                'units' => (float) $enrollment->total_units,
            ] : null,
            'grades' => $grades,
            'average' => $average,
        ]);
    }
}
