<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Student;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $year = AcademicYear::query()->where('is_active', true)->first();

        $pendingCount = Payment::query()->where('status', Payment::PENDING)->count();

        $verifiedToday = Payment::query()
            ->where('status', Payment::VERIFIED)
            ->whereDate('verified_at', today())
            ->sum('amount');

        $collections = LedgerEntry::query()
            ->where('type', 'payment')
            ->when($year, fn ($query) => $query->where('academic_year_id', $year->id))
            ->sum('credit');

        $withBalance = $year
            ? Student::query()
                ->whereHas('enrollments', fn ($query) => $query->where('academic_year_id', $year->id)->where('balance', '>', 0))
                ->count()
            : 0;

        $pendingPayments = Payment::query()
            ->with(['student:id,first_name,last_name,student_number', 'academicYear:id,name'])
            ->where('status', Payment::PENDING)
            ->latest()
            ->take(6)
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'student' => $payment->student?->full_name,
                'student_number' => $payment->student?->student_number,
                'amount' => (float) $payment->amount,
                'method' => $payment->methodLabel(),
                'reference_number' => $payment->reference_number,
                'submitted_at' => $payment->created_at?->toIso8601String(),
            ]);

        $recentVerified = Payment::query()
            ->with(['student:id,first_name,last_name,student_number'])
            ->where('status', Payment::VERIFIED)
            ->latest('verified_at')
            ->take(5)
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'student' => $payment->student?->full_name,
                'amount' => (float) $payment->amount,
                'receipt_number' => $payment->receipt_number,
                'verified_at' => $payment->verified_at?->toIso8601String(),
            ]);

        return Inertia::render('Finance/Dashboard', [
            'year' => $year?->only(['id', 'name']),
            'stats' => [
                'pending' => $pendingCount,
                'verified_today' => (float) $verifiedToday,
                'collections' => (float) $collections,
                'with_balance' => $withBalance,
            ],
            'pendingPayments' => $pendingPayments,
            'recentVerified' => $recentVerified,
        ]);
    }
}
