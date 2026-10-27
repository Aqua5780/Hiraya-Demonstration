<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['System Administrator', 'admin@hiraya.test', Role::ADMIN],
            ['Registrar Office', 'registrar@hiraya.test', Role::REGISTRAR],
            ['Finance Office', 'finance@hiraya.test', Role::FINANCE],
            ['Finance Reviewer', 'finance2@hiraya.test', Role::FINANCE],
        ];

        foreach ($accounts as [$name, $email, $roleSlug]) {
            $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'role_id' => $role->id,
                    'name' => $name,
                    'password' => 'Password123!',
                    'status' => 'active',
                ],
            );
        }
    }
}
