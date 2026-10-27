<?php

namespace App\Services;

use App\Models\ActivationToken;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class OtpService
{
    /**
     * Create a one-time password for the forgot-password flow.
     */
    public function createPasswordReset(User $user): string
    {
        PasswordResetToken::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $otp = str_pad((string) random_int(0, 999999), config('hiraya.auth.otp_length'), '0', STR_PAD_LEFT);

        PasswordResetToken::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'requested_at' => now(),
            'expires_at' => now()->addMinutes(config('hiraya.auth.otp_expiry_minutes')),
            'attempts' => 0,
        ]);

        $this->sendPasswordResetCode($user, $otp);

        return $otp;
    }

    /**
     * Verify an OTP and consume it when valid.
     */
    public function verifyPasswordReset(User $user, string $otp): ?PasswordResetToken
    {
        $token = PasswordResetToken::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $token || ! $token->isValid()) {
            return null;
        }

        if (! Hash::check($otp, $token->otp_hash)) {
            $token->increment('attempts');

            return null;
        }

        $token->forceFill(['used_at' => now()])->save();

        return $token;
    }

    /**
     * Create a single-use account activation token.
     */
    public function createActivation(User $user, ?User $createdBy = null): string
    {
        ActivationToken::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $plain = Str::random(64);

        $token = ActivationToken::create([
            'user_id' => $user->id,
            'token_hash' => Hash::make($plain),
            'expires_at' => now()->addDays(config('hiraya.auth.activation_expiry_days')),
            'created_by' => $createdBy?->id,
        ]);

        $this->sendActivationLink($user, $token, $plain);

        return $plain;
    }

    public function findActivation(string $plain): ?ActivationToken
    {
        return ActivationToken::query()
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->get()
            ->first(fn (ActivationToken $token): bool => Hash::check($plain, $token->token_hash));
    }

    public function consumeActivation(ActivationToken $token): void
    {
        $token->forceFill(['used_at' => now()])->save();
    }

    private function sendPasswordResetCode(User $user, string $otp): void
    {
        $minutes = config('hiraya.auth.otp_expiry_minutes');

        try {
            Mail::raw(
                "Hello {$user->name},\n\nYour Hiraya verification code is {$otp}. It expires in {$minutes} minutes.\n\nIf you did not request this, you may ignore this message.",
                fn ($message) => $message
                    ->to($user->email)
                    ->subject('Hiraya password reset code'),
            );
        } catch (Throwable $exception) {
            Log::warning('Unable to send password reset code.', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendActivationLink(User $user, ActivationToken $token, string $plain): void
    {
        $url = route('activate.show', ['token' => $plain]);

        try {
            Mail::raw(
                "Hello {$user->name},\n\nYour Hiraya account is ready. Activate it using the link below:\n\n{$url}\n\nThis link expires on {$token->expires_at->format('F j, Y g:i A')}.",
                fn ($message) => $message
                    ->to($user->email)
                    ->subject('Activate your Hiraya account'),
            );
        } catch (Throwable $exception) {
            Log::warning('Unable to send account activation link.', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
