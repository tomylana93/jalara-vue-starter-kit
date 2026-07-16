<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class StyleSettingsPolicy
{
    public function view(User $user): bool
    {
        return $user->can(Permission::ManageSettings->value);
    }

    public function update(User $user): bool
    {
        return $user->can(Permission::ManageSettings->value);
    }
}
