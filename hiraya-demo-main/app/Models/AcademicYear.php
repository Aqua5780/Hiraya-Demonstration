<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'start_date', 'end_date', 'enrollment_start', 'enrollment_end', 'status', 'is_active'])]
class AcademicYear extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'enrollment_start' => 'date',
            'enrollment_end' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * @return HasMany<ClassOffering, $this>
     */
    public function classOfferings(): HasMany
    {
        return $this->hasMany(ClassOffering::class);
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return HasMany<FeeStructure, $this>
     */
    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }

    /**
     * @param  Builder<AcademicYear>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isEnrollmentOpen(): bool
    {
        $today = now()->startOfDay();

        return $this->status === 'active'
            && $today->betweenIncluded($this->enrollment_start, $this->enrollment_end);
    }

    public function enrollmentState(): string
    {
        $today = now()->startOfDay();

        if ($this->status !== 'active') {
            return 'closed';
        }

        if ($today->lt($this->enrollment_start)) {
            return 'upcoming';
        }

        if ($today->gt($this->enrollment_end)) {
            return 'closed';
        }

        return 'open';
    }
}
