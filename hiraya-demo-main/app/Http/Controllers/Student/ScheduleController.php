<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __invoke(Request $request, EnrollmentService $enrollmentService): Response
    {
        $student = $request->user()->student;
        abort_if(! $student, 403, 'Your student profile is not configured.');

        $year = AcademicYear::query()->where('is_active', true)->first();
        $enrollment = $year ? $enrollmentService->currentEnrollment($student, $year) : null;

        $classes = $enrollment
            ? $enrollment->details->map(fn ($detail): array => [
                'id' => $detail->id,
                'subject' => $detail->classOffering?->subject?->code,
                'subject_name' => $detail->classOffering?->subject?->name,
                'section' => $detail->classOffering?->section?->name,
                'room' => $detail->classOffering?->room,
                'schedules' => $detail->classOffering?->schedules->map(fn ($schedule): array => [
                    'id' => $schedule->id,
                    'day_of_week' => $schedule->day_of_week,
                    'time' => $schedule->timeRange(),
                    'room' => $schedule->room,
                ])->values() ?? [],
            ])->values()
            : collect();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        $schedule = collect($days)->map(function (string $day) use ($classes): array {
            $items = $classes
                ->flatMap(fn (array $class) => collect($class['schedules'])
                    ->where('day_of_week', $day)
                    ->map(fn (array $schedule): array => [
                        'subject' => $class['subject'],
                        'subject_name' => $class['subject_name'],
                        'section' => $class['section'],
                        'time' => $schedule['time'],
                        'room' => $schedule['room'] ?? $class['room'],
                    ]))
                ->values();

            return ['day' => $day, 'classes' => $items];
        });

        return Inertia::render('Student/Schedule/Index', [
            'year' => $year?->only(['id', 'name']),
            'enrollment' => $enrollment ? [
                'section' => $enrollment->section?->displayName(),
                'units' => (float) $enrollment->total_units,
            ] : null,
            'schedule' => $schedule,
        ]);
    }
}
