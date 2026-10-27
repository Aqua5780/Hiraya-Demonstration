<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\ClassOffering;
use App\Models\FacultyProfile;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Models\YearLevel;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClassOfferingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private AcademicYear $year;

    private Program $program;

    private YearLevel $yearLevel;

    private Section $sectionA;

    private Section $sectionB;

    private Subject $subject;

    private FacultyProfile $faculty;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->admin()->create();
        $facultyUser = User::factory()->faculty()->create();
        $this->faculty = FacultyProfile::create([
            'user_id' => $facultyUser->id,
            'employee_number' => 'EMP-2026-901',
            'department' => 'Tourism',
            'employment_status' => 'full_time',
        ]);

        $this->year = AcademicYear::create([
            'name' => 'SY 2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'enrollment_start' => '2026-10-01',
            'enrollment_end' => '2026-11-30',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->program = Program::create([
            'code' => 'BSTM',
            'name' => 'Tourism Management',
            'level' => 'college',
            'status' => 'active',
        ]);

        $this->yearLevel = YearLevel::create([
            'name' => '1st Year',
            'level' => 'college',
            'sort_order' => 13,
        ]);

        $this->sectionA = Section::create([
            'academic_year_id' => $this->year->id,
            'program_id' => $this->program->id,
            'year_level_id' => $this->yearLevel->id,
            'name' => '1-A',
            'capacity' => 40,
            'status' => 'active',
        ]);

        $this->sectionB = Section::create([
            'academic_year_id' => $this->year->id,
            'program_id' => $this->program->id,
            'year_level_id' => $this->yearLevel->id,
            'name' => '1-B',
            'capacity' => 40,
            'status' => 'active',
        ]);

        $this->subject = Subject::create([
            'code' => 'TM101',
            'name' => 'Fundamentals of Tourism',
            'units' => 3,
            'program_id' => $this->program->id,
            'type' => 'major',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_a_class_offering_with_a_schedule(): void
    {
        $this->actingAs($this->admin)->post('/admin/class-offerings', [
            'academic_year_id' => $this->year->id,
            'section_id' => $this->sectionA->id,
            'subject_id' => $this->subject->id,
            'faculty_id' => $this->faculty->id,
            'room' => 'Room 301',
            'capacity' => 40,
            'status' => 'open',
            'schedules' => [
                ['day_of_week' => 'Monday', 'start_time' => '08:00', 'end_time' => '09:30', 'room' => 'Room 301'],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $offering = ClassOffering::query()->firstOrFail();

        $this->assertSame($this->sectionA->id, $offering->section_id);
        $this->assertSame($this->faculty->id, $offering->faculty_id);
        $this->assertDatabaseHas('class_schedules', [
            'class_offering_id' => $offering->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '09:30',
        ]);
    }

    public function test_same_subject_cannot_be_offered_twice_to_the_same_section(): void
    {
        $this->createOffering($this->sectionA, '08:00', '09:30');

        $this->actingAs($this->admin)->post('/admin/class-offerings', [
            'academic_year_id' => $this->year->id,
            'section_id' => $this->sectionA->id,
            'subject_id' => $this->subject->id,
            'faculty_id' => $this->faculty->id,
            'room' => 'Room 301',
            'capacity' => 40,
            'status' => 'open',
            'schedules' => [
                ['day_of_week' => 'Tuesday', 'start_time' => '08:00', 'end_time' => '09:30', 'room' => 'Room 301'],
            ],
        ])->assertSessionHasErrors('subject_id');
    }

    public function test_faculty_schedule_conflict_is_rejected(): void
    {
        $this->createOffering($this->sectionA, '08:00', '09:30');

        $this->actingAs($this->admin)->post('/admin/class-offerings', [
            'academic_year_id' => $this->year->id,
            'section_id' => $this->sectionB->id,
            'subject_id' => $this->subject->id,
            'faculty_id' => $this->faculty->id,
            'room' => 'Room 302',
            'capacity' => 40,
            'status' => 'open',
            'schedules' => [
                ['day_of_week' => 'Monday', 'start_time' => '09:00', 'end_time' => '10:30', 'room' => 'Room 302'],
            ],
        ])->assertSessionHasErrors('faculty_id');
    }

    public function test_section_schedule_conflict_is_rejected(): void
    {
        $this->createOffering($this->sectionA, '08:00', '09:30');

        $otherSubject = Subject::create([
            'code' => 'TM102',
            'name' => 'Tourism Geography',
            'units' => 3,
            'program_id' => $this->program->id,
            'type' => 'major',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)->post('/admin/class-offerings', [
            'academic_year_id' => $this->year->id,
            'section_id' => $this->sectionA->id,
            'subject_id' => $otherSubject->id,
            'faculty_id' => null,
            'room' => 'Room 301',
            'capacity' => 40,
            'status' => 'open',
            'schedules' => [
                ['day_of_week' => 'Monday', 'start_time' => '08:30', 'end_time' => '10:00', 'room' => 'Room 301'],
            ],
        ])->assertSessionHasErrors('schedules');
    }

    public function test_offerings_page_renders(): void
    {
        $this->createOffering($this->sectionA, '08:00', '09:30');

        $this->actingAs($this->admin)
            ->get('/admin/class-offerings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ClassOfferings/Index')
                ->has('offerings.data', 1)
            );
    }

    private function createOffering(Section $section, string $start, string $end): ClassOffering
    {
        $offering = ClassOffering::create([
            'academic_year_id' => $this->year->id,
            'section_id' => $section->id,
            'subject_id' => $this->subject->id,
            'faculty_id' => $this->faculty->id,
            'room' => 'Room 301',
            'capacity' => 40,
            'status' => 'open',
        ]);

        $offering->schedules()->create([
            'day_of_week' => 'Monday',
            'start_time' => $start,
            'end_time' => $end,
            'room' => 'Room 301',
        ]);

        return $offering;
    }
}
