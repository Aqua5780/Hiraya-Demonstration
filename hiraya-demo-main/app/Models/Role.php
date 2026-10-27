<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description'])]
class Role extends Model
{
    use HasFactory;

    public const ADMIN = 'admin';

    public const REGISTRAR = 'registrar';

    public const FACULTY = 'faculty';

    public const FINANCE = 'finance';

    public const STUDENT = 'student';

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
