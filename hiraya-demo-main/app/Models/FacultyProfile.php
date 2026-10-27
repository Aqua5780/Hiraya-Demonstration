<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'employee_number', 'department', 'employment_status'])]
class FacultyProfile extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ClassOffering, $this>
     */
    public function classOfferings(): HasMany
    {
        return $this->hasMany(ClassOffering::class, 'faculty_id');
    }

    /**
     * @return HasMany<Section, $this>
     */
    public function advisedSections(): HasMany
    {
        return $this->hasMany(Section::class, 'adviser_id');
    }

    /**
     * @return HasMany<Grade, $this>
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'faculty_id');
    }
}
