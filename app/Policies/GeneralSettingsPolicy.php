<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class GeneralSettingsPolicy
{
    /**
     * Determine whether the user can view the general settings.
     */
    public function view(User $user): bool
    {
        return $user->can(Permission::ManageSettings->value);
    }

    /**
     * Determine whether the user can update the general settings.
     */
    public function update(User $user): bool
    {
        return $user->can(Permission::ManageSettings->value);
    }
}
