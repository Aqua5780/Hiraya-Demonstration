<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@hiraya.test')->firstOrFail();
        $faculty = User::query()->where('email', 'faculty@hiraya.test')->firstOrFail();
        $facultySeven = User::query()->where('email', 'faculty7@hiraya.test')->firstOrFail();
        $year = AcademicYear::query()->where('is_active', true)->firstOrFail();

        $welcome = Announcement::query()->firstOrCreate(
            ['title' => 'Welcome to Hiraya'],
            [
                'author_id' => $admin->id,
                'content' => 'Hiraya is now the official student management portal of Good Shepherd of Taguig Colleges, Inc. Students, faculty, registrars, and finance staff can now manage academic records, announcements, schedules, grades, and payments in one place.',
                'audience_type' => 'global',
                'status' => 'published',
                'posted_at' => now()->subDays(5),
            ],
        );
        $welcome->sections()->sync([]);

        $enrollment = Announcement::query()->firstOrCreate(
            ['title' => 'Enrollment for SY 2026-2027 is now open'],
            [
                'author_id' => $admin->id,
                'content' => 'Online enrollment for the active school year is open until November 30, 2026. Log in to your student dashboard, review the offered classes, and submit your enrollment. Please settle any outstanding balance at the finance office or through the payment submission page.',
                'audience_type' => 'global',
                'status' => 'published',
                'posted_at' => now()->subDays(2),
            ],
        );
        $enrollment->sections()->sync([]);

        $bstm = Section::query()
            ->where('academic_year_id', $year->id)
            ->where('name', '1-A')
            ->whereHas('program', fn ($query) => $query->where('code', 'BSTM'))
            ->firstOrFail();

        $fieldTrip = Announcement::query()->firstOrCreate(
            ['title' => 'BSTM 1-A: Tourism field trip orientation'],
            [
                'author_id' => $faculty->id,
                'content' => 'Our field trip orientation will be held on Friday at 2:00 PM in Room 301. Please bring your signed consent form and a photocopy of your guardian\'s valid ID. Attendance is required.',
                'audience_type' => 'section',
                'status' => 'published',
                'posted_at' => now()->subDay(),
            ],
        );
        $fieldTrip->sections()->sync([$bstm->id]);

        $abm = Section::query()
            ->where('academic_year_id', $year->id)
            ->where('name', '11-A')
            ->whereHas('program', fn ($query) => $query->where('code', 'ABM'))
            ->firstOrFail();

        $businessPlan = Announcement::query()->firstOrCreate(
            ['title' => 'ABM 11-A: Business plan consultation'],
            [
                'author_id' => $facultySeven->id,
                'content' => 'Group consultations for the business plan will start next week. Please prepare your initial market analysis and proposed product concept. Consultation slots will be posted on the class roster page.',
                'audience_type' => 'section',
                'status' => 'published',
                'posted_at' => now()->subHours(18),
            ],
        );
        $businessPlan->sections()->sync([$abm->id]);
    }
}
