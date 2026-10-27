<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['academic_year_id', 'program_id', 'year_level_id', 'name', 'capacity', 'adviser_id', 'status'])]
class Section extends Model
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
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<YearLevel, $this>
     */
    public function yearLevel(): BelongsTo
    {
        return $this->belongsTo(YearLevel::class);
    }

    /**
     * @return BelongsTo<FacultyProfile, $this>
     */
    public function adviser(): BelongsTo
    {
        return $this->belongsTo(FacultyProfile::class, 'adviser_id');
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return HasMany<ClassOffering, $this>
     */
    public function classOfferings(): HasMany
    {
        return $this->hasMany(ClassOffering::class);
    }

    /**
     * @return BelongsToMany<Announcement, $this>
     */
    public function announcements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class, 'announcement_sections');
    }

    public function displayName(): string
    {
        $program = $this->program?->code;
        $yearLevel = $this->yearLevel?->name;

        return collect([$program, $yearLevel, $this->name])->filter()->implode(' ');
    }
}
