<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guests_are_redirected_from_account_settings(): void
    {
        $this->get('/settings')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_account_settings(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('account.name', $user->name)
                ->where('account.email', $user->email)
            );
    }

    public function test_name_cannot_be_changed_from_account_settings(): void
    {
        $user = User::factory()->student()->create([
            'name' => 'Original Name',
        ]);

        $this->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Updated Name',
            ])
            ->assertNotFound();

        $this->assertSame('Original Name', $user->fresh()->name);
    }

    public function test_email_address_cannot_be_changed_from_account_settings(): void
    {
        $user = User::factory()->student()->create([
            'email' => 'original@hiraya.test',
        ]);

        $this->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Updated Name',
                'email' => 'changed@hiraya.test',
            ])
            ->assertNotFound();

        $user->refresh();

        $this->assertSame('original@hiraya.test', $user->email);
    }

    public function test_user_can_change_their_password(): void
    {
        $user = User::factory()->student()->create([
            'password' => 'Password123!',
        ]);

        $this->actingAs($user)
            ->put('/settings/password', [
                'current_password' => 'Password123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->student()->create([
            'password' => 'Password123!',
        ]);

        $this->actingAs($user)
            ->put('/settings/password', [
                'current_password' => 'wrong-password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('Password123!', $user->fresh()->password));
    }
}
