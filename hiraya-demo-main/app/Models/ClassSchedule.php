<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['class_offering_id', 'day_of_week', 'start_time', 'end_time', 'room'])]
class ClassSchedule extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<ClassOffering, $this>
     */
    public function classOffering(): BelongsTo
    {
        return $this->belongsTo(ClassOffering::class);
    }

    public function dayShort(): string
    {
        return match ($this->day_of_week) {
            'Monday' => 'Mon',
            'Tuesday' => 'Tue',
            'Wednesday' => 'Wed',
            'Thursday' => 'Thu',
            'Friday' => 'Fri',
            'Saturday' => 'Sat',
            'Sunday' => 'Sun',
            default => $this->day_of_week,
        };
    }

    public function label(): string
    {
        $start = Carbon::parse($this->start_time)->format('g:i A');
        $end = Carbon::parse($this->end_time)->format('g:i A');

        return "{$this->dayShort()} {$start}–{$end}";
    }

    public function timeRange(): string
    {
        $start = Carbon::parse($this->start_time)->format('g:i A');
        $end = Carbon::parse($this->end_time)->format('g:i A');

        return "{$start}–{$end}";
    }
}
