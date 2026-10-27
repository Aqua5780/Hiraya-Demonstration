<?php

namespace App\Features\Reports\Services;

use App\Features\Payroll\Models\PayPeriod;
use App\Models\AcademicYear;
use App\Models\ClassOffering;
use App\Models\Enrollment;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function enrollmentSummary(AcademicYear $year): Collection
    {
        return Enrollment::query()
            ->with([
                'student:id,student_number,first_name,last_name',
                'section:id,name,program_id,year_level_id',
                'section.program:id,code',
                'section.yearLevel:id,name',
            ])
            ->where('academic_year_id', $year->id)
            ->where('status', 'enrolled')
            ->orderBy('section_id')
            ->get()
            ->map(fn (Enrollment $enrollment): array => [
                'student_number' => $enrollment->student?->student_number,
                'student' => $enrollment->student?->full_name,
                'program' => $enrollment->section?->program?->code,
                'year_level' => $enrollment->section?->yearLevel?->name,
                'section' => $enrollment->section?->name,
                'units' => (float) $enrollment->total_units,
                'assessed' => (float) $enrollment->assessed_amount,
                'balance' => (float) $enrollment->balance,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function collectionReport(AcademicYear $year, ?string $from = null, ?string $to = null): Collection
    {
        return Payment::query()
            ->with(['student:id,student_number,first_name,last_name', 'verifiedBy:id,name'])
            ->where('academic_year_id', $year->id)
            ->where('status', Payment::VERIFIED)
            ->when($from, fn ($query) => $query->whereDate('verified_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('verified_at', '<=', $to))
            ->latest('verified_at')
            ->get()
            ->map(fn (Payment $payment): array => [
                'receipt_number' => $payment->receipt_number,
                'student_number' => $payment->student?->student_number,
                'student' => $payment->student?->full_name,
                'amount' => (float) $payment->amount,
                'method' => $payment->methodLabel(),
                'reference_number' => $payment->reference_number,
                'verified_by' => $payment->verifiedBy?->name,
                'verified_at' => $payment->verified_at?->toIso8601String(),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function classList(ClassOffering $offering): Collection
    {
        $offering->load([
            'subject:id,code,name',
            'section:id,name',
            'enrollmentDetails' => fn ($query) => $query
                ->where('status', 'enrolled')
                ->with('enrollment.student:id,student_number,first_name,middle_name,last_name,suffix'),
        ]);

        return $offering->enrollmentDetails->map(fn ($detail): array => [
            'student_number' => $detail->enrollment?->student?->student_number,
            'student' => $detail->enrollment?->student?->full_name,
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function studentLedger(Student $student, AcademicYear $year): Collection
    {
        return LedgerEntry::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $year->id)
            ->latest()
            ->get()
            ->map(fn (LedgerEntry $entry): array => [
                'description' => $entry->description,
                'type' => $entry->type,
                'debit' => (float) $entry->debit,
                'credit' => (float) $entry->credit,
                'balance_after' => (float) $entry->balance_after,
                'created_at' => $entry->created_at?->toIso8601String(),
            ]);
    }

    /**
     * Requires the deferred payroll migration to be enabled.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function payrollRegister(PayPeriod $period): Collection
    {
        return $period->payrolls()
            ->with(['preparedBy:id,name', 'approvedBy:id,name', 'items.faculty.user:id,name'])
            ->latest()
            ->get()
            ->flatMap(fn ($payroll) => $payroll->items->map(fn ($item): array => [
                'pay_period' => $period->name,
                'faculty' => $item->faculty?->user?->name,
                'units' => (float) $item->teaching_units,
                'gross' => (float) $item->gross_amount,
                'allowance' => (float) $item->allowance,
                'deduction' => (float) $item->deduction,
                'net' => (float) $item->net_amount,
                'status' => $payroll->status,
                'prepared_by' => $payroll->preparedBy?->name,
                'approved_by' => $payroll->approvedBy?->name,
            ]));
    }
}
