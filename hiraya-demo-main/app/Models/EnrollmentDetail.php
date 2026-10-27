<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['enrollment_id', 'class_offering_id', 'status'])]
class EnrollmentDetail extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * @return BelongsTo<ClassOffering, $this>
     */
    public function classOffering(): BelongsTo
    {
        return $this->belongsTo(ClassOffering::class);
    }

    /**
     * @return HasOne<Grade, $this>
     */
    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class);
    }
}
