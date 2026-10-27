<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Grade;
use App\Models\Payment;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, EnrollmentService $enrollmentService): Response
    {
        $user = $request->user();
        $student = $user->student;

        if (! $student && ! $user->isAdmin()) {
            abort(403, 'Your student profile is not configured.');
        }

        $isPreview = $student === null;

        if (! $student) {
            $student = new Student([
                'user_id' => $user->id,
                'student_number' => 'ADMIN',
                'first_name' => $user->name,
                'last_name' => '',
            ]);
        }

        $year = AcademicYear::query()->where('is_active', true)->first();
        $enrollment = $year ? $enrollmentService->currentEnrollment($student, $year) : null;

        $sectionIds = $enrollment?->details
            ?->map(fn ($detail) => $detail->classOffering?->section_id)
            ->filter()
            ->unique()
            ->values()
            ->all() ?? [];

        $announcements = Announcement::query()
            ->with('author:id,name')
            ->where('status', 'published')
            ->where(function ($query) use ($sectionIds): void {
                $query->where('audience_type', 'global');

                if ($sectionIds !== []) {
                    $query->orWhereHas('sections', fn ($query) => $query->whereIn('sections.id', $sectionIds));
                }
            })
            ->latest('posted_at')
            ->take(5)
            ->get()
            ->map(fn (Announcement $announcement): array => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'author' => $announcement->author?->name,
                'audience' => $announcement->audience_type,
                'posted_at' => $announcement->posted_at?->toIso8601String(),
            ]);

        $grades = $enrollment
            ? Grade::query()
                ->whereIn('enrollment_detail_id', $enrollment->details->pluck('id'))
                ->where('status', 'submitted')
                ->get()
            : collect();

        $balance = $year ? $enrollmentService->balanceFor($student, $year) : 0;

        $pendingPayments = Payment::query()
            ->where('student_id', $student->id)
            ->where('status', Payment::PENDING)
            ->count();

        $schedule = $enrollment
            ? $enrollment->details
                ->flatMap(fn ($detail) => $detail->classOffering?->schedules?->map(fn ($schedule) => [
                    'subject' => $detail->classOffering?->subject?->code,
                    'day' => $schedule->day_of_week,
                    'time' => $schedule->timeRange(),
                    'room' => $schedule->room,
                ]) ?? [])
                ->sortBy(fn (array $item): int => array_search($item['day'], ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'], true))
                ->values()
            : collect();

        return Inertia::render('Student/Dashboard', [
            'year' => $year?->only(['id', 'name', 'enrollment_start', 'enrollment_end', 'status']),
            'enrollmentState' => $year?->enrollmentState(),
            'isPreview' => $isPreview,
            'student' => [
                'student_number' => $student->student_number,
                'name' => $student->full_name,
                'program' => $student->program?->code,
                'year_level' => $student->yearLevel?->name,
            ],
            'enrollment' => $enrollment ? [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'section' => $enrollment->section?->displayName(),
                'units' => (float) $enrollment->total_units,
                'assessed_amount' => (float) $enrollment->assessed_amount,
                'balance' => $balance,
                'classes' => $enrollment->details->map(fn ($detail): array => [
                    'id' => $detail->id,
                    'subject' => $detail->classOffering?->subject?->code,
                    'subject_name' => $detail->classOffering?->subject?->name,
                    'schedule' => $detail->classOffering?->scheduleLabel(),
                    'room' => $detail->classOffering?->room,
                    'grade' => $detail->grade?->final_rating,
                ])->values(),
            ] : null,
            'stats' => [
                'units' => $enrollment ? (float) $enrollment->total_units : 0,
                'balance' => $balance,
                'graded_subjects' => $grades->count(),
                'pending_payments' => $pendingPayments,
            ],
            'announcements' => $announcements,
            'schedule' => $schedule,
        ]);
    }
}
