<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Section;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function __invoke(Request $request, EnrollmentService $enrollmentService): Response
    {
        $student = $request->user()->student;
        abort_if(! $student, 403, 'Your student profile is not configured.');

        $year = AcademicYear::query()->where('is_active', true)->first();
        $enrollment = $year ? $enrollmentService->currentEnrollment($student, $year) : null;
        $sectionIds = $enrollment
            ? $enrollment->details
                ->map(fn ($detail) => $detail->classOffering?->section_id)
                ->filter()
                ->unique()
                ->values()
                ->all()
            : [];

        $announcements = Announcement::query()
            ->with([
                'author:id,name',
                'sections:id,name,program_id,year_level_id',
                'sections.program:id,code',
                'sections.yearLevel:id,name',
            ])
            ->where('status', 'published')
            ->where(function ($query) use ($sectionIds): void {
                $query->where('audience_type', 'global');

                if ($sectionIds !== []) {
                    $query->orWhereHas('sections', fn ($query) => $query->whereIn('sections.id', $sectionIds));
                }
            })
            ->latest('posted_at')
            ->paginate(10)
            ->through(fn (Announcement $announcement): array => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'audience_type' => $announcement->audience_type,
                'author' => $announcement->author?->name,
                'posted_at' => $announcement->posted_at?->toIso8601String(),
                'attachment_name' => $announcement->attachment_name,
                'sections' => $announcement->sections->map(fn (Section $section): array => [
                    'id' => $section->id,
                    'name' => $section->name,
                    'program' => $section->program?->code,
                    'year_level' => $section->yearLevel?->name,
                ])->values(),
            ]);

        return Inertia::render('Student/Announcements/Index', [
            'year' => $year?->only(['id', 'name']),
            'enrollment' => $enrollment ? [
                'section' => $enrollment->section?->displayName(),
                'section_id' => $enrollment->section_id,
            ] : null,
            'announcements' => $announcements,
        ]);
    }
}
