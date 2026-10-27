<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [Role::ADMIN, 'Administrator', 'Full institutional oversight and system configuration.'],
            [Role::REGISTRAR, 'Registrar', 'Student records, enrollment oversight, and bulk record imports.'],
            [Role::FACULTY, 'Faculty', 'Class lists, grade encoding, and class announcements.'],
            [Role::FINANCE, 'Finance', 'Payment verification, student ledgers, and financial records.'],
            [Role::STUDENT, 'Student', 'Self-service enrollment, schedules, grades, announcements, and payments.'],
        ];

        foreach ($roles as [$slug, $name, $description]) {
            Role::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $description],
            );
        }
    }
}
