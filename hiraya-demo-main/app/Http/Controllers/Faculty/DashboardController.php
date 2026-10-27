<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\ClassOffering;
use App\Models\EnrollmentDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $faculty = $user->facultyProfile;

        if (! $faculty && ! $user->isAdmin()) {
            abort(403, 'Your faculty profile is not configured.');
        }

        $year = AcademicYear::query()->where('is_active', true)->first();

        $offerings = $faculty
            ? ClassOffering::query()
                ->with(['subject:id,code,name,units', 'section:id,name,program_id,year_level_id', 'section.program:id,code', 'section.yearLevel:id,name', 'schedules'])
                ->withCount(['enrollmentDetails as enrolled_count' => fn ($query) => $query->where('status', 'enrolled')])
                ->where('faculty_id', $faculty->id)
                ->when($year, fn ($query) => $query->where('academic_year_id', $year->id))
                ->get()
            : collect();

        $offeringIds = $offerings->pluck('id');

        $studentCount = EnrollmentDetail::query()
            ->whereIn('class_offering_id', $offeringIds)
            ->where('status', 'enrolled')
            ->count();

        $pendingGrades = EnrollmentDetail::query()
            ->whereIn('class_offering_id', $offeringIds)
            ->where('status', 'enrolled')
            ->whereDoesntHave('grade')
            ->count();

        $today = now()->format('l');

        $todayClasses = $offerings
            ->filter(fn (ClassOffering $offering): bool => $offering->schedules->contains('day_of_week', $today))
            ->map(fn (ClassOffering $offering): array => [
                'id' => $offering->id,
                'subject' => $offering->subject?->code,
                'section' => $offering->section?->name,
                'time' => $offering->schedules->firstWhere('day_of_week', $today)?->timeRange(),
                'room' => $offering->room,
                'students' => $offering->enrolled_count,
            ])
            ->values();

        $announcements = Announcement::query()
            ->where('author_id', $user->id)
            ->latest('posted_at')
            ->take(4)
            ->get()
            ->map(fn (Announcement $announcement): array => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'posted_at' => $announcement->posted_at?->toIso8601String(),
            ]);

        return Inertia::render('Faculty/Dashboard', [
            'year' => $year?->only(['id', 'name']),
            'faculty' => [
                'employee_number' => $faculty?->employee_number ?? 'Administrator preview',
                'department' => $faculty?->department ?? 'No faculty profile assigned',
            ],
            'isPreview' => $faculty === null,
            'stats' => [
                'classes' => $offerings->count(),
                'students' => $studentCount,
                'pending_grades' => $pendingGrades,
                'announcements' => $announcements->count(),
            ],
            'todayClasses' => $todayClasses,
            'classes' => $offerings->map(fn (ClassOffering $offering): array => [
                'id' => $offering->id,
                'subject' => $offering->subject?->code,
                'subject_name' => $offering->subject?->name,
                'section' => $offering->section?->name,
                'program' => $offering->section?->program?->code,
                'year_level' => $offering->section?->yearLevel?->name,
                'schedule' => $offering->scheduleLabel(),
                'room' => $offering->room,
                'students' => $offering->enrolled_count,
            ])->values(),
            'announcements' => $announcements,
        ]);
    }
}
