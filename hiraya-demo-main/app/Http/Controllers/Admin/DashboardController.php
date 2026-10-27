<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Enrollment;
use App\Models\FacultyProfile;
use App\Models\Payment;
use App\Models\Section;
use App\Models\Student;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $year = AcademicYear::query()->where('is_active', true)->first();

        $stats = [
            'students' => Student::query()->where('status', 'active')->count(),
            'faculty' => FacultyProfile::query()->count(),
            'sections' => $year ? Section::query()->where('academic_year_id', $year->id)->count() : 0,
            'enrolled' => $year ? Enrollment::query()->where('academic_year_id', $year->id)->where('status', 'enrolled')->count() : 0,
            'pending_payments' => Payment::query()->where('status', Payment::PENDING)->count(),
            'announcements' => Announcement::query()->where('status', 'published')->count(),
        ];

        $announcements = Announcement::query()
            ->with('author:id,name')
            ->where('status', 'published')
            ->latest('posted_at')
            ->take(4)
            ->get()
            ->map(fn (Announcement $announcement): array => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'author' => $announcement->author?->name,
                'audience' => $announcement->audience_type,
                'posted_at' => $announcement->posted_at?->toIso8601String(),
            ]);

        $pendingPayments = Payment::query()
            ->with(['student:id,first_name,last_name,student_number'])
            ->where('status', Payment::PENDING)
            ->latest()
            ->take(4)
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'student' => $payment->student?->full_name,
                'student_number' => $payment->student?->student_number,
                'amount' => (float) $payment->amount,
                'method' => $payment->methodLabel(),
                'created_at' => $payment->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'year' => $year?->only(['id', 'name', 'status', 'enrollment_start', 'enrollment_end', 'is_active']),
            'enrollmentState' => $year?->enrollmentState(),
            'stats' => $stats,
            'announcements' => $announcements,
            'pendingPayments' => $pendingPayments,
        ]);
    }
}
