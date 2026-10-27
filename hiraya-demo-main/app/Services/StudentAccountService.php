<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentAccountService
{
    public function __construct(private readonly OtpService $otpService) {}

    public function previewEmail(Student $student): string
    {
        $base = $this->baseLocalPart($student);
        $domain = trim((string) config('hiraya.student_email_domain', 'gstc.edu.ph'), '@');
        $email = "{$base}@{$domain}";
        $suffix = 2;

        while (
            User::query()->where('email', $email)->exists()
            || Student::query()
                ->where('id', '!=', $student->id)
                ->where('email', $email)
                ->exists()
        ) {
            $email = "{$base}{$suffix}@{$domain}";
            $suffix++;
        }

        return $email;
    }

    /**
     * @return array{user: User, activation_url: string}
     *
     * @throws ValidationException
     */
    public function create(Student $student, User $actor): array
    {
        if ($student->user_id !== null) {
            throw ValidationException::withMessages([
                'student_id' => 'This student already has a login account.',
            ]);
        }

        $role = Role::query()->where('slug', Role::STUDENT)->firstOrFail();
        $email = $this->previewEmail($student);

        $user = DB::transaction(function () use ($student, $role, $email): User {
            $user = User::create([
                'role_id' => $role->id,
                'name' => $student->full_name,
                'email' => $email,
                'password' => Str::random(40),
                'status' => 'pending',
            ]);

            $student->forceFill(['user_id' => $user->id])->save();

            return $user;
        });

        $token = $this->otpService->createActivation($user, $actor);
        $activationUrl = route('activate.show', ['token' => $token]);

        AuditLogger::log('student.account_created', 'users', $user->id, null, [
            'student_id' => $student->id,
            'email' => $email,
        ], $actor);

        return [
            'user' => $user->load('role'),
            'activation_url' => $activationUrl,
        ];
    }

    private function baseLocalPart(Student $student): string
    {
        $firstName = Str::of($student->first_name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();
        $lastName = Str::of($student->last_name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();
        $base = trim("{$firstName}.{$lastName}", '.');

        if ($base === '') {
            $digits = Str::of($student->student_number)
                ->replaceMatches('/[^0-9]/', '')
                ->toString();
            $base = 'student'.$digits;
        }

        return Str::limit($base, 64, '');
    }
}
