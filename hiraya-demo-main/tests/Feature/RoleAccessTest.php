<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_each_seeded_role_can_access_its_dashboard(): void
    {
        $accounts = [
            'admin@hiraya.test' => 'Admin/Dashboard',
            'registrar@hiraya.test' => 'Registrar/Dashboard',
            'faculty@hiraya.test' => 'Faculty/Dashboard',
            'finance@hiraya.test' => 'Finance/Dashboard',
            'student@hiraya.test' => 'Student/Dashboard',
        ];

        foreach ($accounts as $email => $component) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->actingAs($user)
                ->get(route($user->dashboardRoute(), absolute: false))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }

    public function test_registrar_cannot_access_finance(): void
    {
        $registrar = User::query()->where('email', 'registrar@hiraya.test')->firstOrFail();

        $this->actingAs($registrar)->get('/finance')->assertForbidden();
    }

    public function test_student_cannot_access_registrar(): void
    {
        $student = User::query()->where('email', 'student@hiraya.test')->firstOrFail();

        $this->actingAs($student)->get('/registrar')->assertForbidden();
    }

    public function test_admin_can_access_other_role_dashboards(): void
    {
        $admin = User::query()->where('email', 'admin@hiraya.test')->firstOrFail();

        $this->actingAs($admin)->get('/registrar')->assertOk();
        $this->actingAs($admin)->get('/finance')->assertOk();
        $this->actingAs($admin)->get('/faculty')->assertOk();
        $this->actingAs($admin)->get('/student')->assertOk();
    }

    public function test_help_page_is_available_to_an_authenticated_user(): void
    {
        $student = User::query()->where('email', 'student@hiraya.test')->firstOrFail();

        $this->actingAs($student)
            ->get('/help')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Help/Index')
                ->where('role', 'student')
            );
    }
}
