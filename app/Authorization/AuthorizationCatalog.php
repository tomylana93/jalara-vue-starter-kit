<?php

namespace App\Authorization;

use App\Enums\Permission;
use App\Enums\Role;

final class AuthorizationCatalog
{
    /** @return list<Role> */
    public function roles(): array
    {
        return [Role::SuperAdmin];
    }

    /** @return list<Permission> */
    public function permissions(): array
    {
        return [Permission::ManageSettings];
    }

    /** @return list<Permission> */
    public function permissionsFor(Role $role): array
    {
        return match ($role) {
            Role::SuperAdmin => [Permission::ManageSettings],
        };
    }
}
