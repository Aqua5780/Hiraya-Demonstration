<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class AuthTimingService
{
    /**
     * A bcrypt hash used only to keep failed-login and reset flows doing the
     * same expensive password work when no matching account exists.
     */
    private const DUMMY_PASSWORD_HASH = '$2y$12$DHqF1z5M2.EWsdwWqQgBUuxGd3HNtaC4WOxhBaZPqFHM47d6F83Ny';

    public static function simulatePasswordCheck(string $password): void
    {
        Hash::check($password, self::DUMMY_PASSWORD_HASH);
    }
}
