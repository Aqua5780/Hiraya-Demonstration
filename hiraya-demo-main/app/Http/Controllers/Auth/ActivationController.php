<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ActivateAccountRequest;
use App\Services\AuditLogger;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ActivationController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

    public function show(string $token): Response
    {
        $activation = $this->otpService->findActivation($token);

        return Inertia::render('Auth/Activate', [
            'token' => $token,
            'valid' => $activation !== null,
            'invitedUser' => $activation ? [
                'name' => $activation->user->name,
                'email' => $activation->user->email,
            ] : null,
        ]);
    }

    public function store(ActivateAccountRequest $request): RedirectResponse
    {
        $activation = $this->otpService->findActivation($request->string('token')->toString());

        if (! $activation) {
            throw ValidationException::withMessages([
                'token' => 'This activation link is invalid or has expired. Ask the administrator for a new invitation.',
            ]);
        }

        $user = $activation->user;

        $user->forceFill([
            'password' => $request->string('password')->toString(),
            'status' => 'active',
            'failed_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $this->otpService->consumeActivation($activation);

        AuditLogger::log('auth.account_activated', 'users', $user->id, ['status' => 'pending'], ['status' => 'active'], $user);

        return redirect()->route('login')->with('success', 'Your account is now active. You can sign in.');
    }
}
