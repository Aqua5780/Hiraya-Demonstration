<?php

namespace Tests\Feature\Admin;

use App\Models\ActivationToken;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountInviteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_invite_a_faculty_account(): void
    {
        $this->actingAs($this->admin)->post('/admin/accounts/invite', [
            'name' => 'New Faculty',
            'email' => 'new.faculty@hiraya.test',
            'role' => 'faculty',
            'department' => 'Business',
        ])->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('demo_activation_url');

        $user = User::query()->where('email', 'new.faculty@hiraya.test')->firstOrFail();

        $this->assertSame('pending', $user->status);
        $this->assertDatabaseHas('faculty_profiles', [
            'user_id' => $user->id,
            'department' => 'Business',
        ]);
        $this->assertDatabaseHas('activation_tokens', [
            'user_id' => $user->id,
            'used_at' => null,
        ]);
    }

    public function test_activation_link_can_be_resent(): void
    {
        $this->actingAs($this->admin)->post('/admin/accounts/invite', [
            'name' => 'New Registrar',
            'email' => 'new.registrar@hiraya.test',
            'role' => 'registrar',
        ])->assertRedirect();

        $user = User::query()->where('email', 'new.registrar@hiraya.test')->firstOrFail();
        $oldTokenId = ActivationToken::query()->where('user_id', $user->id)->value('id');

        $this->actingAs($this->admin)
            ->post("/admin/accounts/{$user->id}/resend")
            ->assertRedirect()
            ->assertSessionHas('demo_activation_url');

        $this->assertNotSame($oldTokenId, ActivationToken::query()->where('user_id', $user->id)->whereNull('used_at')->value('id'));
    }

    public function test_admin_can_create_a_student_account_with_a_school_email(): void
    {
        $student = Student::create([
            'student_number' => '2026-9100',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->post('/admin/accounts/students', ['student_id' => $student->id])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('demo_activation_url');

        $user = User::query()->where('email', 'juan.delacruz@gstc.edu.ph')->firstOrFail();

        $this->assertSame('student', $user->role?->slug);
        $this->assertSame('pending', $user->status);
        $this->assertSame($user->id, $student->fresh()->user_id);
        $this->assertDatabaseHas('activation_tokens', [
            'user_id' => $user->id,
            'used_at' => null,
        ]);
    }

    public function test_student_account_emails_receive_a_numeric_suffix_on_collision(): void
    {
        $first = Student::create([
            'student_number' => '2026-9101',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'status' => 'active',
        ]);
        $second = Student::create([
            'student_number' => '2026-9102',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)->post('/admin/accounts/students', ['student_id' => $first->id]);
        $this->actingAs($this->admin)->post('/admin/accounts/students', ['student_id' => $second->id]);

        $this->assertDatabaseHas('users', ['email' => 'juan.delacruz@gstc.edu.ph']);
        $this->assertDatabaseHas('users', ['email' => 'juan.delacruz2@gstc.edu.ph']);
    }

    public function test_accounts_page_lists_students_without_accounts(): void
    {
        Student::create([
            'student_number' => '2026-9103',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/accounts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('studentsWithoutAccounts', 1)
                ->where('studentsWithoutAccounts.0.preview_email', 'maria.santos@gstc.edu.ph')
            );
    }

    public function test_admin_can_change_a_student_password(): void
    {
        $student = User::factory()->student()->create([
            'password' => 'Password123!',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->patch("/admin/accounts/{$student->id}/password", [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewPassword123!', $student->fresh()->password));
        $this->assertSame('active', $student->fresh()->status);
    }

    public function test_admin_cannot_change_a_non_student_password_from_the_accounts_screen(): void
    {
        $faculty = User::factory()->faculty()->create();

        $this->actingAs($this->admin)
            ->patch("/admin/accounts/{$faculty->id}/password", [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertForbidden();
    }

    public function test_non_admin_cannot_create_student_accounts(): void
    {
        $student = Student::create([
            'student_number' => '2026-9104',
            'first_name' => 'Jose',
            'last_name' => 'Rizal',
            'status' => 'active',
        ]);
        $registrar = User::factory()->registrar()->create();

        $this->actingAs($registrar)
            ->post('/admin/accounts/students', ['student_id' => $student->id])
            ->assertForbidden();
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $this->actingAs($this->admin)
            ->patch("/admin/accounts/{$this->admin->id}/status", ['status' => 'inactive'])
            ->assertStatus(422);
    }
}
