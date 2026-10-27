<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\AuthTimingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    private const GENERIC_LOGIN_ERROR = 'The provided credentials do not match our records.';

    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route($request->user()->dashboardRoute());
        }

        $demoAccounts = config('hiraya.demo_mode')
            ? collect(config('hiraya.demo_accounts'))
                ->map(fn (array $account): array => [
                    ...$account,
                    'name' => User::query()->where('email', $account['email'])->value('name') ?? $account['role'],
                ])
                ->all()
            : [];

        return Inertia::render('Auth/Login', [
            'demoAccounts' => $demoAccounts,
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $email = strtolower($credentials['email']);
        $credentials['email'] = $email;

        $throttleKey = 'login:'.mb_strtolower($email).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please wait a moment before trying again.',
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            AuthTimingService::simulatePasswordCheck($credentials['password']);

            throw ValidationException::withMessages([
                'email' => self::GENERIC_LOGIN_ERROR,
            ]);
        }

        if ($user->isLocked()) {
            AuthTimingService::simulatePasswordCheck($credentials['password']);

            AuditLogger::log('auth.login_locked', 'users', $user->id, null, ['locked_until' => $user->locked_until], $user);

            throw ValidationException::withMessages([
                'email' => self::GENERIC_LOGIN_ERROR,
            ]);
        }

        if (! $user->isActive()) {
            AuthTimingService::simulatePasswordCheck($credentials['password']);

            throw ValidationException::withMessages([
                'email' => self::GENERIC_LOGIN_ERROR,
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->recordFailedAttempt($user);

            throw ValidationException::withMessages([
                'email' => self::GENERIC_LOGIN_ERROR,
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user->forceFill([
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ])->save();

        AuditLogger::log('auth.login', 'users', $user->id, null, ['email' => $user->email], $user);

        return redirect()->intended(route($user->dashboardRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            AuditLogger::log('auth.logout', 'users', $user->id, null, null, $user);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function recordFailedAttempt(User $user): void
    {
        $maxAttempts = (int) config('hiraya.auth.max_attempts');
        $attempts = $user->failed_attempts + 1;
        $lockedUntil = null;

        if ($attempts >= $maxAttempts) {
            $lockedUntil = now()->addMinutes((int) config('hiraya.auth.lockout_minutes'));
        }

        $user->forceFill([
            'failed_attempts' => $attempts,
            'locked_until' => $lockedUntil,
        ])->save();

        AuditLogger::log('auth.login_failed', 'users', $user->id, null, [
            'attempts' => $attempts,
            'locked_until' => $lockedUntil,
        ], $user);
    }
}
