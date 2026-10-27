<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ClassOffering;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\FeeStructure;
use App\Models\LedgerEntry;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    /**
     * @return Collection<int, ClassOffering>
     */
    public function eligibleOfferings(Student $student, AcademicYear $year): Collection
    {
        return ClassOffering::query()
            ->with(['subject', 'section.program', 'section.yearLevel', 'faculty.user', 'schedules'])
            ->where('academic_year_id', $year->id)
            ->where('status', 'open')
            ->whereHas('section', function ($query) use ($student): void {
                $query->where('program_id', $student->program_id);

                if ($student->year_level_id !== null) {
                    $query->where('year_level_id', $student->year_level_id);
                }
            })
            ->get()
            ->sortBy(fn (ClassOffering $offering): string => $offering->subject?->code ?? '')
            ->values();
    }

    public function currentEnrollment(Student $student, AcademicYear $year): ?Enrollment
    {
        return Enrollment::query()
            ->with(['section.program', 'section.yearLevel', 'details.classOffering.subject', 'details.classOffering.schedules', 'details.grade'])
            ->where('student_id', $student->id)
            ->where('academic_year_id', $year->id)
            ->first();
    }

    /**
     * Validate a student's class selection.
     *
     * @param  array<int, int>  $offeringIds
     * @return Collection<int, ClassOffering>
     *
     * @throws ValidationException
     */
    public function validateSelection(Student $student, AcademicYear $year, array $offeringIds, bool $force = false): Collection
    {
        if (! $force && ! $year->isEnrollmentOpen()) {
            throw ValidationException::withMessages([
                'classes' => 'Enrollment is not open for the selected academic year.',
            ]);
        }

        $offeringIds = array_values(array_unique(array_map('intval', $offeringIds)));

        if ($offeringIds === []) {
            throw ValidationException::withMessages([
                'classes' => 'Select at least one class to enroll in.',
            ]);
        }

        $offerings = ClassOffering::query()
            ->with(['subject', 'section', 'schedules'])
            ->whereIn('id', $offeringIds)
            ->get();

        if ($offerings->count() !== count($offeringIds)) {
            throw ValidationException::withMessages([
                'classes' => 'One or more selected classes could not be found.',
            ]);
        }

        $existing = $this->currentEnrollment($student, $year);

        if ($existing && $existing->status === 'enrolled') {
            throw ValidationException::withMessages([
                'classes' => 'You are already enrolled for this academic year.',
            ]);
        }

        $seenSubjects = [];

        foreach ($offerings as $offering) {
            if ($offering->academic_year_id !== $year->id) {
                throw ValidationException::withMessages([
                    'classes' => 'A selected class does not belong to the active academic year.',
                ]);
            }

            if ($offering->status !== 'open') {
                throw ValidationException::withMessages([
                    'classes' => "{$offering->subject?->code} is no longer open for enrollment.",
                ]);
            }

            if ($offering->section?->program_id !== $student->program_id) {
                throw ValidationException::withMessages([
                    'classes' => 'A selected class is not part of your program.',
                ]);
            }

            if ($student->year_level_id !== null && $offering->section?->year_level_id !== $student->year_level_id) {
                throw ValidationException::withMessages([
                    'classes' => 'A selected class is not part of your year level.',
                ]);
            }

            if (isset($seenSubjects[$offering->subject_id])) {
                throw ValidationException::withMessages([
                    'classes' => 'You cannot select the same subject twice.',
                ]);
            }

            $seenSubjects[$offering->subject_id] = true;

            if ($offering->enrolledCount() >= $offering->capacity) {
                throw ValidationException::withMessages([
                    'classes' => "{$offering->subject?->code} is already full.",
                ]);
            }
        }

        $this->assertNoScheduleConflicts($offerings);

        return $offerings;
    }

    /**
     * @param  array<int, int>  $offeringIds
     *
     * @throws ValidationException
     */
    public function enroll(
        Student $student,
        AcademicYear $year,
        array $offeringIds,
        ?User $actor = null,
        bool $force = false,
    ): Enrollment {
        $offerings = $this->validateSelection($student, $year, $offeringIds, $force);

        return DB::transaction(function () use ($student, $year, $offerings, $actor): Enrollment {
            $enrollment = Enrollment::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $year->id)
                ->lockForUpdate()
                ->first();

            if ($enrollment && $enrollment->status === 'enrolled') {
                throw ValidationException::withMessages([
                    'classes' => 'You are already enrolled for this academic year.',
                ]);
            }

            $sectionId = $offerings->first()?->section_id;
            $totalUnits = $offerings->sum(fn (ClassOffering $offering): float => (float) ($offering->subject?->units ?? 0));
            $assessment = $this->assessmentFor($student, $year);

            if (! $enrollment) {
                $enrollment = Enrollment::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                    'section_id' => $sectionId,
                    'status' => 'enrolled',
                    'total_units' => $totalUnits,
                    'assessed_amount' => $assessment,
                    'balance' => $assessment,
                    'enrolled_at' => now(),
                ]);
            } else {
                $enrollment->details()->delete();
                $enrollment->forceFill([
                    'section_id' => $sectionId,
                    'status' => 'enrolled',
                    'total_units' => $totalUnits,
                    'assessed_amount' => $assessment,
                    'balance' => $assessment,
                    'enrolled_at' => now(),
                ])->save();
            }

            foreach ($offerings as $offering) {
                EnrollmentDetail::create([
                    'enrollment_id' => $enrollment->id,
                    'class_offering_id' => $offering->id,
                    'status' => 'enrolled',
                ]);
            }

            $this->recordCharge($enrollment, $student, $year, $assessment, $actor);

            AuditLogger::log(
                'enrollment.submitted',
                'enrollments',
                $enrollment->id,
                null,
                [
                    'academic_year_id' => $year->id,
                    'classes' => $offerings->pluck('id')->all(),
                    'total_units' => $totalUnits,
                    'assessed_amount' => $assessment,
                ],
                $actor,
            );

            return $enrollment->load(['section.program', 'section.yearLevel', 'details.classOffering.subject']);
        });
    }

    public function assessmentFor(Student $student, AcademicYear $year): float
    {
        $fee = FeeStructure::query()
            ->where('academic_year_id', $year->id)
            ->where('program_id', $student->program_id)
            ->where('status', 'active')
            ->where(function ($query) use ($student): void {
                $query->whereNull('year_level_id');

                if ($student->year_level_id !== null) {
                    $query->orWhere('year_level_id', $student->year_level_id);
                }
            })
            ->orderByRaw('year_level_id IS NULL')
            ->first();

        return (float) ($fee?->total_amount ?? 0);
    }

    public function balanceFor(Student $student, AcademicYear $year): float
    {
        $totals = LedgerEntry::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $year->id)
            ->selectRaw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total')
            ->first();

        return round((float) ($totals?->debit_total ?? 0) - (float) ($totals?->credit_total ?? 0), 2);
    }

    private function recordCharge(Enrollment $enrollment, Student $student, AcademicYear $year, float $assessment, ?User $actor): void
    {
        $alreadyCharged = LedgerEntry::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $year->id)
            ->where('type', 'charge')
            ->exists();

        if ($alreadyCharged || $assessment <= 0) {
            $enrollment->forceFill(['balance' => $this->balanceFor($student, $year)])->save();

            return;
        }

        LedgerEntry::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'type' => 'charge',
            'description' => "Enrollment assessment for {$year->name}",
            'debit' => $assessment,
            'credit' => 0,
            'balance_after' => $assessment,
            'created_by' => $actor?->id,
        ]);

        $enrollment->forceFill(['balance' => $assessment])->save();
    }

    /**
     * @param  Collection<int, ClassOffering>  $offerings
     *
     * @throws ValidationException
     */
    private function assertNoScheduleConflicts(Collection $offerings): void
    {
        $offerings = $offerings->values();

        for ($i = 0; $i < $offerings->count(); $i++) {
            for ($j = $i + 1; $j < $offerings->count(); $j++) {
                $first = $offerings[$i];
                $second = $offerings[$j];

                foreach ($first->schedules as $a) {
                    foreach ($second->schedules as $b) {
                        if ($this->schedulesOverlap($a, $b)) {
                            throw ValidationException::withMessages([
                                'classes' => sprintf(
                                    'Schedule conflict: %s and %s overlap on %s.',
                                    $first->subject?->code,
                                    $second->subject?->code,
                                    $a->day_of_week,
                                ),
                            ]);
                        }
                    }
                }
            }
        }
    }

    private function schedulesOverlap(ClassSchedule $a, ClassSchedule $b): bool
    {
        return $a->day_of_week === $b->day_of_week
            && $a->start_time < $b->end_time
            && $a->end_time > $b->start_time;
    }
}
