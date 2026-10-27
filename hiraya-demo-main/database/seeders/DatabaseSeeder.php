<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Demo data seeding is disabled in production.');

            return;
        }

        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            AcademicSeeder::class,
            StudentSeeder::class,
            AnnouncementSeeder::class,
            HelpSeeder::class,
        ]);
    }
}
