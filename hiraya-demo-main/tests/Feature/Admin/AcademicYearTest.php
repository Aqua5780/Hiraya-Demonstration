<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AcademicYearTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_view_academic_years(): void
    {
        AcademicYear::create([
            'name' => 'SY 2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'enrollment_start' => '2026-10-01',
            'enrollment_end' => '2026-11-30',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/academic-years')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AcademicYears/Index')
                ->has('years', 1)
                ->where('years.0.name', 'SY 2026-2027')
            );
    }

    public function test_admin_can_create_and_activate_an_academic_year(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/academic-years', [
                'name' => 'SY 2026-2027',
                'start_date' => '2026-06-01',
                'end_date' => '2027-03-31',
                'enrollment_start' => '2026-10-01',
                'enrollment_end' => '2026-11-30',
                'status' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $year = AcademicYear::query()->where('name', 'SY 2026-2027')->firstOrFail();

        $this->assertTrue($year->is_active);
        $this->assertSame('active', $year->status);
    }

    public function test_activating_a_year_deactivates_the_previous_year(): void
    {
        $old = AcademicYear::create([
            'name' => 'SY 2025-2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-03-31',
            'enrollment_start' => '2025-05-01',
            'enrollment_end' => '2025-06-15',
            'status' => 'active',
            'is_active' => true,
        ]);

        $new = AcademicYear::create([
            'name' => 'SY 2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'enrollment_start' => '2026-10-01',
            'enrollment_end' => '2026-11-30',
            'status' => 'upcoming',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/academic-years/{$new->id}/activate")
            ->assertRedirect();

        $this->assertTrue($new->fresh()->is_active);
        $this->assertFalse($old->fresh()->is_active);
        $this->assertSame('completed', $old->fresh()->status);
    }

    public function test_school_year_name_must_follow_the_expected_format(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/academic-years', [
                'name' => '2026-2027',
                'start_date' => '2026-06-01',
                'end_date' => '2027-03-31',
                'enrollment_start' => '2026-10-01',
                'enrollment_end' => '2026-11-30',
                'status' => 'upcoming',
            ])
            ->assertSessionHasErrors('name');
    }
}
