<?php

namespace App\Features\Payroll\Services;

use App\Features\Payroll\Models\CompensationRule;
use App\Features\Payroll\Models\PayPeriod;
use App\Features\Payroll\Models\Payroll;
use App\Models\ClassOffering;
use App\Models\FacultyProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    /**
     * Generate a draft payroll for a pay period from each faculty member's
     * confirmed teaching load and active compensation rule.
     *
     * @throws ValidationException
     */
    public function generateDraft(PayPeriod $period, User $actor): Payroll
    {
        if ($period->status !== 'open') {
            throw ValidationException::withMessages([
                'pay_period' => 'This pay period is closed.',
            ]);
        }

        $existing = Payroll::query()->where('pay_period_id', $period->id)->first();

        if ($existing && ! in_array($existing->status, [Payroll::DRAFT, Payroll::RETURNED], true)) {
            throw ValidationException::withMessages([
                'pay_period' => 'This pay period already has a submitted payroll.',
            ]);
        }

        return DB::transaction(function () use ($period, $actor, $existing): Payroll {
            $payroll = $existing ?? new Payroll([
                'pay_period_id' => $period->id,
                'prepared_by' => $actor->id,
            ]);

            $payroll->forceFill([
                'status' => Payroll::DRAFT,
                'approved_by' => null,
                'approved_at' => null,
                'submitted_at' => null,
                'remarks' => null,
            ])->save();

            $payroll->items()->delete();

            $total = 0;

            $facultyMembers = FacultyProfile::query()->with('user:id,name')->get();

            foreach ($facultyMembers as $faculty) {
                $units = (float) ClassOffering::query()
                    ->where('academic_year_id', $period->academic_year_id)
                    ->where('faculty_id', $faculty->id)
                    ->whereHas('subject')
                    ->with('subject:id,units')
                    ->get()
                    ->sum(fn (ClassOffering $offering): float => (float) ($offering->subject?->units ?? 0));

                if ($units <= 0) {
                    continue;
                }

                $rule = $this->ruleFor($faculty, $period);

                if (! $rule) {
                    throw ValidationException::withMessages([
                        'compensation' => "No active compensation rule was found for {$faculty->user?->name}.",
                    ]);
                }

                $gross = $rule->basis === 'monthly'
                    ? (float) $rule->rate
                    : round($units * (float) $rule->rate, 2);
                $net = round($gross + (float) $rule->allowance - (float) $rule->deduction, 2);

                $payroll->items()->create([
                    'faculty_id' => $faculty->id,
                    'teaching_units' => $units,
                    'rate' => $rule->rate,
                    'allowance' => $rule->allowance,
                    'deduction' => $rule->deduction,
                    'gross_amount' => $gross,
                    'net_amount' => $net,
                ]);

                $total += $net;
            }

            $payroll->forceFill(['total_amount' => round($total, 2)])->save();

            AuditLogger::log('payroll.draft_generated', 'payrolls', $payroll->id, null, [
                'pay_period_id' => $period->id,
                'total_amount' => $payroll->total_amount,
            ], $actor);

            return $payroll->fresh(['payPeriod', 'items.faculty.user']);
        });
    }

    /**
     * Submit a draft payroll for maker-checker approval.
     */
    public function submit(Payroll $payroll, User $actor): Payroll
    {
        if (! in_array($payroll->status, [Payroll::DRAFT, Payroll::RETURNED], true)) {
            throw ValidationException::withMessages([
                'payroll' => 'Only draft or returned payrolls can be submitted.',
            ]);
        }

        $payroll->forceFill([
            'status' => Payroll::SUBMITTED,
            'submitted_at' => now(),
        ])->save();

        AuditLogger::log('payroll.submitted', 'payrolls', $payroll->id, null, ['status' => Payroll::SUBMITTED], $actor);

        return $payroll->fresh();
    }

    /**
     * Approve a submitted payroll. The approver must be different from the preparer.
     */
    public function approve(Payroll $payroll, User $approver, ?string $remarks = null): Payroll
    {
        if ($payroll->status !== Payroll::SUBMITTED) {
            throw ValidationException::withMessages([
                'payroll' => 'Only submitted payrolls can be approved.',
            ]);
        }

        if ($payroll->prepared_by === $approver->id) {
            throw ValidationException::withMessages([
                'approver' => 'The preparer cannot approve the same payroll.',
            ]);
        }

        $payroll->forceFill([
            'status' => Payroll::APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'remarks' => $remarks,
        ])->save();

        AuditLogger::log('payroll.approved', 'payrolls', $payroll->id, null, [
            'status' => Payroll::APPROVED,
            'approved_by' => $approver->id,
        ], $approver);

        return $payroll->fresh();
    }

    public function post(Payroll $payroll, User $actor): Payroll
    {
        if ($payroll->status !== Payroll::APPROVED) {
            throw ValidationException::withMessages([
                'payroll' => 'Only approved payrolls can be posted.',
            ]);
        }

        $payroll->forceFill(['status' => Payroll::POSTED])->save();

        AuditLogger::log('payroll.posted', 'payrolls', $payroll->id, null, ['status' => Payroll::POSTED], $actor);

        return $payroll->fresh();
    }

    public function returnToPreparer(Payroll $payroll, User $actor, string $remarks): Payroll
    {
        if ($payroll->status !== Payroll::SUBMITTED) {
            throw ValidationException::withMessages([
                'payroll' => 'Only submitted payrolls can be returned.',
            ]);
        }

        $payroll->forceFill([
            'status' => Payroll::RETURNED,
            'remarks' => $remarks,
        ])->save();

        AuditLogger::log('payroll.returned', 'payrolls', $payroll->id, null, ['remarks' => $remarks], $actor);

        return $payroll->fresh();
    }

    private function ruleFor(FacultyProfile $faculty, PayPeriod $period): ?CompensationRule
    {
        return CompensationRule::query()
            ->where('status', 'active')
            ->where(function ($query) use ($faculty): void {
                $query->where('faculty_id', $faculty->id)->orWhereNull('faculty_id');
            })
            ->where('effective_from', '<=', $period->end_date)
            ->where(function ($query) use ($period): void {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $period->start_date);
            })
            ->orderByRaw('faculty_id IS NULL')
            ->first();
    }
}
