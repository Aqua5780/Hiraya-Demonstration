<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Models\YearLevel;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_create_programs_year_levels_sections_and_subjects(): void
    {
        $year = AcademicYear::create([
            'name' => 'SY 2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'enrollment_start' => '2026-10-01',
            'enrollment_end' => '2026-11-30',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post('/admin/master-data/programs', [
            'code' => 'BSTM',
            'name' => 'Bachelor of Science in Tourism Management',
            'level' => 'college',
            'description' => null,
            'status' => 'active',
        ])->assertRedirect();

        $program = Program::query()->where('code', 'BSTM')->firstOrFail();

        $this->actingAs($this->admin)->post('/admin/master-data/year-levels', [
            'name' => '1st Year',
            'level' => 'college',
            'sort_order' => 13,
        ])->assertRedirect();

        $yearLevel = YearLevel::query()->where('name', '1st Year')->firstOrFail();

        $this->actingAs($this->admin)->post('/admin/master-data/sections', [
            'academic_year_id' => $year->id,
            'program_id' => $program->id,
            'year_level_id' => $yearLevel->id,
            'name' => '1-A',
            'capacity' => 40,
            'adviser_id' => null,
            'status' => 'active',
        ])->assertRedirect();

        $section = Section::query()->where('name', '1-A')->firstOrFail();

        $this->actingAs($this->admin)->post('/admin/master-data/subjects', [
            'code' => 'TM101',
            'name' => 'Fundamentals of Tourism',
            'units' => 3,
            'program_id' => $program->id,
            'year_level_id' => $yearLevel->id,
            'type' => 'major',
            'status' => 'active',
        ])->assertRedirect();

        $subject = Subject::query()->where('code', 'TM101')->firstOrFail();

        $this->assertSame($program->id, $section->program_id);
        $this->assertSame($year->id, $section->academic_year_id);
        $this->assertSame($program->id, $subject->program_id);
    }

    public function test_master_data_page_renders_existing_records(): void
    {
        $program = Program::create([
            'code' => 'BPA',
            'name' => 'Bachelor in Public Administration',
            'level' => 'college',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/master-data')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/MasterData/Index')
                ->has('programs', 1)
                ->where('programs.0.code', $program->code)
            );
    }

    public function test_program_code_must_be_unique(): void
    {
        Program::create([
            'code' => 'BSTM',
            'name' => 'Tourism',
            'level' => 'college',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)->post('/admin/master-data/programs', [
            'code' => 'BSTM',
            'name' => 'Another Tourism Program',
            'level' => 'college',
            'status' => 'active',
        ])->assertSessionHasErrors('code');
    }
}
