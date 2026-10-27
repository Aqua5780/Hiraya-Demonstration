<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AcademicYearService
{
    /**
     * Make the given academic year the only active year.
     */
    public function activate(AcademicYear $year, ?User $actor = null): void
    {
        DB::transaction(function () use ($year, $actor): void {
            $oldValues = [
                'active_year_id' => AcademicYear::query()->where('is_active', true)->value('id'),
            ];

            AcademicYear::query()
                ->whereKeyNot($year->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'status' => 'completed',
                ]);

            $year->forceFill([
                'is_active' => true,
                'status' => 'active',
            ])->save();

            AuditLogger::log(
                'academic_year.activated',
                'academic_years',
                $year->id,
                $oldValues,
                ['active_year_id' => $year->id, 'name' => $year->name],
                $actor,
            );
        });
    }
}
