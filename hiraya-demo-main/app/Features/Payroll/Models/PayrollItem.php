<?php

namespace App\Features\Payroll\Models;

use App\Models\FacultyProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payroll_id', 'faculty_id', 'teaching_units', 'rate', 'allowance', 'deduction', 'gross_amount', 'net_amount'])]
class PayrollItem extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'teaching_units' => 'decimal:1',
            'rate' => 'decimal:2',
            'allowance' => 'decimal:2',
            'deduction' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Payroll, $this>
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * @return BelongsTo<FacultyProfile, $this>
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(FacultyProfile::class, 'faculty_id');
    }
}
