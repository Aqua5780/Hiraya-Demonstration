<?php

namespace Tests\Feature\Auth;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->has('demoAccounts', 6)
            );
    }

    public function test_active_user_can_sign_in(): void
    {
        $user = User::factory()->admin()->create([
            'password' => 'Password123!',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertSame(0, $user->failed_attempts);
        $this->assertNull($user->locked_until);
        $this->assertNotNull($user->last_login_at);
    }

    public function test_invalid_password_records_a_failed_attempt(): void
    {
        $user = User::factory()->admin()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $user->refresh();
        $this->assertSame(1, $user->failed_attempts);
    }

    public function test_account_locks_after_three_failed_attempts(): void
    {
        $user = User::factory()->admin()->create([
            'password' => 'Password123!',
        ]);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $user->refresh();
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_sign_in(): void
    {
        $user = User::factory()->admin()->create([
            'password' => 'Password123!',
            'status' => 'pending',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_user_is_logged_out_on_the_next_request(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user);

        $user->forceFill(['status' => 'inactive'])->save();

        $this->get('/student')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_failed_login_does_not_reveal_account_status(): void
    {
        $inactive = User::factory()->admin()->create([
            'password' => 'Password123!',
            'status' => 'inactive',
        ]);
        $expected = 'The provided credentials do not match our records.';

        $this->post('/login', [
            'email' => 'missing@hiraya.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors(['email' => $expected]);

        $this->post('/login', [
            'email' => $inactive->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors(['email' => $expected]);
    }

    public function test_authenticated_user_is_redirected_to_their_dashboard(): void
    {
        $user = User::factory()->registrar()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect(route('registrar.dashboard'));
    }

    public function test_student_can_access_their_dashboard(): void
    {
        $user = User::factory()->student()->create();
        $student = Student::create([
            'user_id' => $user->id,
            'student_number' => '2026-9001',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/student')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Dashboard')
                ->where('student.student_number', $student->student_number)
            );
    }

    public function test_student_cannot_access_the_admin_dashboard(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_access_registrar_and_finance_dashboards(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/registrar')->assertOk();
        $this->actingAs($admin)->get('/finance')->assertOk();
    }
}
