<?php

namespace Tests\Feature\Auth;

use App\Models\ActivationToken;
use App\Models\User;
use App\Services\OtpService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_valid_activation_link_renders_the_activation_page(): void
    {
        $user = User::factory()->faculty()->create(['status' => 'pending']);
        $token = app(OtpService::class)->createActivation($user);

        $this->get("/activate/{$token}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Activate')
                ->where('valid', true)
                ->where('invitedUser.email', $user->email)
            );
    }

    public function test_an_invalid_activation_link_is_flagged(): void
    {
        $this->get('/activate/not-a-real-token')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Activate')
                ->where('valid', false)
            );
    }

    public function test_user_can_activate_their_account(): void
    {
        $user = User::factory()->faculty()->create(['status' => 'pending']);
        $token = app(OtpService::class)->createActivation($user);

        $this->post('/activate', [
            'token' => $token,
            'password' => 'Activated123!',
            'password_confirmation' => 'Activated123!',
        ])->assertRedirect(route('login'));

        $user->refresh();

        $this->assertSame('active', $user->status);
        $this->assertTrue(Hash::check('Activated123!', $user->password));
        $this->assertNotNull(ActivationToken::query()->where('user_id', $user->id)->first()?->used_at);
    }

    public function test_used_activation_link_cannot_be_reused(): void
    {
        $user = User::factory()->faculty()->create(['status' => 'pending']);
        $token = app(OtpService::class)->createActivation($user);
        app(OtpService::class)->consumeActivation(ActivationToken::query()->where('user_id', $user->id)->firstOrFail());

        $this->get("/activate/{$token}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('valid', false));
    }
}
