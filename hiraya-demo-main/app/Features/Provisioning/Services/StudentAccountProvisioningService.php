<?php

namespace App\Features\Provisioning\Services;

use App\Features\Provisioning\Models\AccountProvisioning;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentAccountProvisioningService
{
    public function __construct(private readonly OtpService $otpService) {}

    public function previewEmail(Student $student): string
    {
        $surname = Str::of($student->last_name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();
        $accountNumber = Str::of($student->student_number)->replaceMatches('/[^0-9]/', '')->toString();
        $base = $surname.$accountNumber;
        $email = "{$base}@gstc.edu.ph";
        $suffix = 2;

        while (User::query()->where('email', $email)->exists()) {
            $email = "{$base}{$suffix}@gstc.edu.ph";
            $suffix++;
        }

        return $email;
    }

    /**
     * @return array{provisioning: AccountProvisioning, activation_url: string}
     *
     * @throws ValidationException
     */
    public function provision(Student $student, User $actor): array
    {
        if ($student->user_id !== null) {
            throw ValidationException::withMessages([
                'student_id' => 'This student already has a login account.',
            ]);
        }

        $studentRole = Role::query()->where('slug', Role::STUDENT)->firstOrFail();
        $email = $this->previewEmail($student);

        $user = DB::transaction(function () use ($student, $studentRole, $email): User {
            $user = User::create([
                'role_id' => $studentRole->id,
                'name' => $student->full_name,
                'email' => $email,
                'password' => Str::random(40),
                'status' => 'pending',
            ]);

            $student->forceFill(['user_id' => $user->id, 'email' => $email])->save();

            return $user;
        });

        $token = $this->otpService->createActivation($user, $actor);
        $activationUrl = route('activate.show', ['token' => $token]);

        $provisioning = AccountProvisioning::create([
            'student_id' => $student->id,
            'email' => $email,
            'status' => 'created',
            'message' => 'Activation link generated.',
            'created_by' => $actor->id,
        ]);

        AuditLogger::log('student.account_provisioned', 'account_provisionings', $provisioning->id, null, [
            'student_id' => $student->id,
            'email' => $email,
        ], $actor);

        return [
            'provisioning' => $provisioning,
            'activation_url' => $activationUrl,
        ];
    }
}
