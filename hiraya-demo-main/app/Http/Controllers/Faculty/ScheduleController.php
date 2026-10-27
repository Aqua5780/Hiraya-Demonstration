<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassOffering;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $faculty = $request->user()->facultyProfile;
        abort_if(! $faculty, 403, 'Your faculty profile is not configured.');

        $year = AcademicYear::query()->where('is_active', true)->first();

        $offerings = ClassOffering::query()
            ->with([
                'subject:id,code,name,units',
                'section:id,name,program_id,year_level_id',
                'section.program:id,code',
                'section.yearLevel:id,name',
                'schedules',
            ])
            ->where('faculty_id', $faculty->id)
            ->when($year, fn ($query) => $query->where('academic_year_id', $year->id))
            ->get();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        $schedule = collect($days)->map(function (string $day) use ($offerings): array {
            $items = $offerings
                ->flatMap(fn (ClassOffering $offering) => $offering->schedules
                    ->where('day_of_week', $day)
                    ->map(fn ($schedule): array => [
                        'id' => $schedule->id,
                        'offering_id' => $offering->id,
                        'subject' => $offering->subject?->code,
                        'subject_name' => $offering->subject?->name,
                        'section' => $offering->section?->name,
                        'program' => $offering->section?->program?->code,
                        'year_level' => $offering->section?->yearLevel?->name,
                        'time' => $schedule->timeRange(),
                        'room' => $schedule->room ?? $offering->room,
                    ]))
                ->sortBy('time')
                ->values();

            return ['day' => $day, 'classes' => $items];
        });

        return Inertia::render('Faculty/Schedule/Index', [
            'year' => $year?->only(['id', 'name']),
            'faculty' => [
                'employee_number' => $faculty->employee_number,
                'department' => $faculty->department,
            ],
            'schedule' => $schedule,
        ]);
    }
}
