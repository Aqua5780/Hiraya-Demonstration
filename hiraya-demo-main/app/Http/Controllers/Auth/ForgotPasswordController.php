<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendPasswordResetRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\AuthTimingService;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    private const GENERIC_OTP_ERROR = 'The verification code is invalid or has expired. Please request a new code.';

    public function __construct(private readonly OtpService $otpService) {}

    public function show(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function send(SendPasswordResetRequest $request): RedirectResponse
    {
        $email = strtolower($request->string('email')->toString());
        $throttleKey = 'password-reset-request:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            throw ValidationException::withMessages([
                'email' => 'Too many password reset requests. Please wait before trying again.',
            ]);
        }

        RateLimiter::hit($throttleKey, 600);

        $user = User::query()->where('email', $email)->first();

        $request->session()->put('password_reset_email', $email);
        $request->session()->forget('password_reset_user_id');

        if ($user) {
            $otp = $this->otpService->createPasswordReset($user);

            $request->session()->put('password_reset_user_id', $user->id);

            if (config('hiraya.demo_mode')) {
                $request->session()->flash('demo_otp', $otp);
            }

            AuditLogger::log('auth.password_reset_requested', 'users', $user->id, null, null, $user);
        } else {
            AuthTimingService::simulatePasswordCheck($email);
        }

        return redirect()->route('password.verify')->with(
            'success',
            'If an account exists for that email, a reset code has been sent.',
        );
    }

    public function showVerify(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/VerifyOtp', [
            'email' => $email,
        ]);
    }

    public function verify(VerifyOtpRequest $request): RedirectResponse
    {
        $email = (string) $request->session()->get('password_reset_email', '');
        $userId = $request->session()->get('password_reset_user_id');
        $user = $userId ? User::find($userId) : null;

        if ($email === '') {
            return redirect()->route('password.request');
        }

        $throttleKey = 'password-reset-verify:'.strtolower($email).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            throw ValidationException::withMessages([
                'otp' => 'Too many verification attempts. Please wait before trying again.',
            ]);
        }

        RateLimiter::hit($throttleKey, 600);

        if (! $user) {
            AuthTimingService::simulatePasswordCheck($request->string('otp')->toString());

            throw ValidationException::withMessages([
                'otp' => self::GENERIC_OTP_ERROR,
            ]);
        }

        $token = $this->otpService->verifyPasswordReset($user, $request->string('otp')->toString());

        if (! $token) {
            throw ValidationException::withMessages([
                'otp' => self::GENERIC_OTP_ERROR,
            ]);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->put('password_reset_token_id', $token->id);
        $request->session()->forget('password_reset_email');

        return redirect()->route('password.reset');
    }

    public function showReset(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('password_reset_user_id') || ! $request->session()->has('password_reset_token_id')) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/ResetPassword');
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $userId = $request->session()->get('password_reset_user_id');
        $tokenId = $request->session()->get('password_reset_token_id');

        $token = $tokenId
            ? PasswordResetToken::query()->where('user_id', $userId)->whereKey($tokenId)->first()
            : null;

        if (! $token || $token->used_at === null || ! $token->expires_at->isFuture()) {
            throw ValidationException::withMessages([
                'password' => 'Your password reset session has expired. Please start again.',
            ]);
        }

        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('password.request');
        }

        $user->forceFill([
            'password' => $request->string('password')->toString(),
            'failed_attempts' => 0,
            'locked_until' => null,
        ])->save();

        AuditLogger::log('auth.password_reset', 'users', $user->id, null, null, $user);

        $request->session()->forget([
            'password_reset_user_id',
            'password_reset_email',
            'password_reset_token_id',
        ]);

        return redirect()->route('login')->with('success', 'Your password has been reset. You can now sign in.');
    }
}
