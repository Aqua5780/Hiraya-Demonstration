<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enrollment_detail_id', 'faculty_id', 'midterm', 'final', 'final_rating', 'remarks', 'status', 'encoded_at'])]
class Grade extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'midterm' => 'decimal:2',
            'final' => 'decimal:2',
            'final_rating' => 'decimal:2',
            'encoded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EnrollmentDetail, $this>
     */
    public function enrollmentDetail(): BelongsTo
    {
        return $this->belongsTo(EnrollmentDetail::class);
    }

    /**
     * @return BelongsTo<FacultyProfile, $this>
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(FacultyProfile::class, 'faculty_id');
    }
}
