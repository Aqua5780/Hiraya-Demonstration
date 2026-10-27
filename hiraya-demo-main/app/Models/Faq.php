<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['faq_category_id', 'question', 'answer', 'role_visibility', 'sort_order', 'is_published'])]
class Faq extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<FaqCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }

    /**
     * @param  Builder<Faq>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * @param  Builder<Faq>  $query
     */
    public function scopeForRole(Builder $query, ?string $role): void
    {
        if ($role === Role::ADMIN) {
            return;
        }

        $query->where(function (Builder $query) use ($role): void {
            $query->whereNull('role_visibility')
                ->orWhere('role_visibility', 'all')
                ->orWhere('role_visibility', $role);
        });
    }
}
