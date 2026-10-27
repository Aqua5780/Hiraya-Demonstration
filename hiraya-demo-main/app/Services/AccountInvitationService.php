<?php

namespace App\Services;

use App\Models\FacultyProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountInvitationService
{
    public function __construct(private readonly OtpService $otpService) {}

    /**
     * Create a pending account and return its activation URL.
     *
     * @param  array{name: string, email: string, role: string, department?: string|null}  $data
     * @return array{user: User, activation_url: string}
     *
     * @throws ValidationException
     */
    public function invite(array $data, User $actor): array
    {
        $role = Role::query()->where('slug', $data['role'])->first();

        if (! $role || ! in_array($role->slug, [Role::ADMIN, Role::REGISTRAR, Role::FACULTY, Role::FINANCE], true)) {
            throw ValidationException::withMessages([
                'role' => 'That role cannot be invited from this screen.',
            ]);
        }

        $user = DB::transaction(function () use ($data, $role, $actor): User {
            $user = User::create([
                'role_id' => $role->id,
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => Str::random(40),
                'status' => 'pending',
            ]);

            if ($role->slug === Role::FACULTY) {
                FacultyProfile::create([
                    'user_id' => $user->id,
                    'employee_number' => $this->nextEmployeeNumber(),
                    'department' => $data['department'] ?? null,
                    'employment_status' => 'full_time',
                ]);
            }

            AuditLogger::log('account.invited', 'users', $user->id, null, [
                'email' => $user->email,
                'role' => $role->slug,
            ], $actor);

            return $user;
        });

        $token = $this->otpService->createActivation($user, $actor);

        return [
            'user' => $user->load('role'),
            'activation_url' => route('activate.show', ['token' => $token]),
        ];
    }

    /**
     * Issue a fresh activation link for a pending user.
     *
     * @return array{user: User, activation_url: string}
     *
     * @throws ValidationException
     */
    public function resend(User $user, User $actor): array
    {
        if ($user->status === 'active') {
            throw ValidationException::withMessages([
                'user' => 'This account is already active.',
            ]);
        }

        $token = $this->otpService->createActivation($user, $actor);

        AuditLogger::log('account.activation_resent', 'users', $user->id, null, null, $actor);

        return [
            'user' => $user,
            'activation_url' => route('activate.show', ['token' => $token]),
        ];
    }

    private function nextEmployeeNumber(): string
    {
        $sequence = FacultyProfile::query()->count() + 1;

        do {
            $number = 'EMP-'.now()->format('Y').'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while (FacultyProfile::query()->where('employee_number', $number)->exists());

        return $number;
    }
}
