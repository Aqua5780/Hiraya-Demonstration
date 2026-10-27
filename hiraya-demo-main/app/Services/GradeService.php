<?php

namespace App\Services;

use App\Models\EnrollmentDetail;
use App\Models\FacultyProfile;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class GradeService
{
    public function computeFinalRating(?float $midterm, ?float $final): ?float
    {
        if ($midterm === null && $final === null) {
            return null;
        }

        $midterm ??= 0;
        $final ??= $midterm;

        $rating = ($midterm * (float) config('hiraya.grades.midterm_weight'))
            + ($final * (float) config('hiraya.grades.final_weight'));

        return round($rating, 2);
    }

    /**
     * @param  array{midterm: float|int|null, final: float|int|null}  $data
     *
     * @throws ValidationException
     */
    public function save(EnrollmentDetail $detail, array $data, FacultyProfile $faculty, ?User $actor = null): Grade
    {
        $offering = $detail->classOffering;

        if ($offering?->faculty_id !== $faculty->id) {
            throw ValidationException::withMessages([
                'grade' => 'You can only encode grades for your assigned classes.',
            ]);
        }

        $midterm = $data['midterm'] !== null ? round((float) $data['midterm'], 2) : null;
        $final = $data['final'] !== null ? round((float) $data['final'], 2) : null;

        foreach (['midterm' => $midterm, 'final' => $final] as $field => $value) {
            if ($value !== null && ($value < 0 || $value > 100)) {
                throw ValidationException::withMessages([
                    $field => 'Grades must be between 0 and 100.',
                ]);
            }
        }

        $rating = $this->computeFinalRating($midterm, $final);
        $remarks = $rating === null
            ? null
            : ($rating >= (float) config('hiraya.grades.passing_grade') ? 'Passed' : 'Failed');

        $grade = Grade::query()->firstOrNew(['enrollment_detail_id' => $detail->id]);
        $oldValues = $grade->exists ? $grade->only(['midterm', 'final', 'final_rating', 'remarks']) : null;

        $grade->fill([
            'faculty_id' => $faculty->id,
            'midterm' => $midterm,
            'final' => $final,
            'final_rating' => $rating,
            'remarks' => $remarks,
            'status' => 'submitted',
            'encoded_at' => now(),
        ])->save();

        AuditLogger::log(
            'grade.encoded',
            'grades',
            $grade->id,
            $oldValues,
            $grade->only(['midterm', 'final', 'final_rating', 'remarks']),
            $actor,
        );

        return $grade;
    }
}
