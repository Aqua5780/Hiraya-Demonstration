<?php

namespace App\Features\Payroll\Models;

use App\Models\FacultyProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['faculty_id', 'name', 'basis', 'rate', 'allowance', 'deduction', 'effective_from', 'effective_to', 'status'])]
class CompensationRule extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'allowance' => 'decimal:2',
            'deduction' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    /**
     * @return BelongsTo<FacultyProfile, $this>
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(FacultyProfile::class, 'faculty_id');
    }
}
