<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\OtpService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_forgot_password_creates_an_otp_and_redirects_to_verification(): void
    {
        $user = User::factory()->student()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.verify'))
            ->assertSessionHas('demo_otp');

        $this->assertDatabaseCount('password_reset_tokens', 1);
        $this->assertDatabaseHas('password_reset_tokens', [
            'user_id' => $user->id,
            'used_at' => null,
        ]);
    }

    public function test_unknown_email_gets_the_same_neutral_response(): void
    {
        $this->post('/forgot-password', ['email' => 'nobody@hiraya.test'])
            ->assertRedirect(route('password.verify'))
            ->assertSessionHas('password_reset_email', 'nobody@hiraya.test')
            ->assertSessionHas('success', 'If an account exists for that email, a reset code has been sent.');

        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_unknown_email_cannot_complete_verification(): void
    {
        $this->withSession([
            'password_reset_email' => 'nobody@hiraya.test',
        ])->post('/forgot-password/verify', ['otp' => '123456'])
            ->assertSessionHasErrors([
                'otp' => 'The verification code is invalid or has expired. Please request a new code.',
            ]);
    }

    public function test_otp_can_be_verified_and_password_can_be_reset(): void
    {
        $user = User::factory()->student()->create();
        $otp = app(OtpService::class)->createPasswordReset($user);
        $token = PasswordResetToken::query()->where('user_id', $user->id)->latest('id')->firstOrFail();

        $this->withSession([
            'password_reset_user_id' => $user->id,
            'password_reset_email' => $user->email,
        ])->post('/forgot-password/verify', ['otp' => $otp])
            ->assertRedirect(route('password.reset'));

        $this->post('/reset-password', [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertNotNull($token->fresh()->used_at);
    }

    public function test_incorrect_otp_is_rejected(): void
    {
        $user = User::factory()->student()->create();
        $otp = app(OtpService::class)->createPasswordReset($user);
        $wrongOtp = $otp === '111111' ? '222222' : '111111';

        $this->withSession([
            'password_reset_user_id' => $user->id,
            'password_reset_email' => $user->email,
        ])->post('/forgot-password/verify', ['otp' => $wrongOtp])
            ->assertSessionHasErrors('otp');

        $this->assertDatabaseHas('password_reset_tokens', [
            'user_id' => $user->id,
            'attempts' => 1,
        ]);
    }

    public function test_expired_otp_is_rejected(): void
    {
        $user = User::factory()->student()->create();
        $otp = app(OtpService::class)->createPasswordReset($user);

        PasswordResetToken::query()->where('user_id', $user->id)->update([
            'expires_at' => now()->subMinute(),
        ]);

        $this->withSession([
            'password_reset_user_id' => $user->id,
            'password_reset_email' => $user->email,
        ])->post('/forgot-password/verify', ['otp' => $otp])
            ->assertSessionHasErrors('otp');
    }

    public function test_otp_cannot_be_used_after_five_failed_attempts(): void
    {
        $user = User::factory()->student()->create();
        $otp = app(OtpService::class)->createPasswordReset($user);
        $wrongOtp = $otp === '111111' ? '222222' : '111111';
        $session = [
            'password_reset_user_id' => $user->id,
            'password_reset_email' => $user->email,
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withSession($session)
                ->post('/forgot-password/verify', ['otp' => $wrongOtp])
                ->assertSessionHasErrors('otp');
        }

        $this->withSession($session)
            ->post('/forgot-password/verify', ['otp' => $otp])
            ->assertSessionHasErrors('otp');

        $this->assertDatabaseHas('password_reset_tokens', [
            'user_id' => $user->id,
            'attempts' => 5,
        ]);
    }

    public function test_password_reset_requests_are_rate_limited(): void
    {
        $user = User::factory()->student()->create();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post('/forgot-password', ['email' => $user->email])
                ->assertRedirect(route('password.verify'));
        }

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasErrors('email');
    }

    public function test_password_reset_does_not_reactivate_an_inactive_account(): void
    {
        $user = User::factory()->student()->create(['status' => 'inactive']);
        $otp = app(OtpService::class)->createPasswordReset($user);

        $this->withSession([
            'password_reset_user_id' => $user->id,
            'password_reset_email' => $user->email,
        ])->post('/forgot-password/verify', ['otp' => $otp])
            ->assertRedirect(route('password.reset'));

        $this->post('/reset-password', [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect(route('login'));

        $this->assertSame('inactive', $user->fresh()->status);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'NewPassword123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
