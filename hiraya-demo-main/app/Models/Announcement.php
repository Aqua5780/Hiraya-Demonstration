<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'author_id',
    'title',
    'content',
    'audience_type',
    'status',
    'posted_at',
    'attachment_path',
    'attachment_name',
    'attachment_mime',
    'attachment_size',
])]
class Announcement extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
            'attachment_size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsToMany<Section, $this>
     */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'announcement_sections');
    }

    /**
     * @param  Builder<Announcement>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published');
    }

    public function isGlobal(): bool
    {
        return $this->audience_type === 'global';
    }
}
