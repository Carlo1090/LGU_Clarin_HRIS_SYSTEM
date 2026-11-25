<?php

namespace App\Support;

use App\Models\User;

class HrisEnrollmentPolicy
{
    public static function offers(User $user, string $feature): bool
    {
        $roles = config('employee_activation.role_security');
        $role = $user->role?->name;

        return ! isset($roles[$role]) || ($roles[$role][$feature] ?? false) === true;
    }
}
