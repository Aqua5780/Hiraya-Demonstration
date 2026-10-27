<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user()->loadMissing(['student', 'facultyProfile']);

        return Inertia::render('Settings/Index', [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'identifier' => $user->student?->student_number ?? $user->facultyProfile?->employee_number,
                'identifier_label' => $user->student
                    ? 'Student number'
                    : ($user->facultyProfile ? 'Employee number' : null),
            ],
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user->forceFill([
            'password' => $validated['password'],
            'failed_attempts' => 0,
            'locked_until' => null,
        ])->save();

        AuditLogger::log('account.password_updated', 'users', $user->id, null, null, $user);

        return back()->with('success', 'Password updated.');
    }
}
