<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['academic_year_id', 'section_id', 'subject_id', 'faculty_id', 'room', 'capacity', 'status'])]
class ClassOffering extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return BelongsTo<FacultyProfile, $this>
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(FacultyProfile::class, 'faculty_id');
    }

    /**
     * @return HasMany<ClassSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    /**
     * @return HasMany<EnrollmentDetail, $this>
     */
    public function enrollmentDetails(): HasMany
    {
        return $this->hasMany(EnrollmentDetail::class);
    }

    public function enrolledCount(): int
    {
        return $this->enrollmentDetails()->where('status', 'enrolled')->count();
    }

    public function availableSlots(): int
    {
        return max(0, $this->capacity - $this->enrolledCount());
    }

    public function scheduleLabel(): string
    {
        return $this->schedules
            ->map(fn (ClassSchedule $schedule): string => $schedule->label())
            ->implode(' • ');
    }
}
