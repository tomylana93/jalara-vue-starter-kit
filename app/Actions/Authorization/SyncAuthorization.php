<?php

namespace App\Actions\Authorization;

use App\Authorization\AuthorizationCatalog;
use App\Enums\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as PermissionRole;
use Spatie\Permission\PermissionRegistrar;

final readonly class SyncAuthorization
{
    public function __construct(
        private AuthorizationCatalog $catalog,
        private PermissionRegistrar $permissionRegistrar,
    ) {}

    public function handle(bool $dryRun = false): AuthorizationSyncResult
    {
        $result = $this->calculateResult($dryRun);

        if ($dryRun) {
            return $result;
        }

        $this->applyCatalog();
        $this->permissionRegistrar->forgetCachedPermissions();

        return $result;
    }

    private function calculateResult(bool $dryRun): AuthorizationSyncResult
    {
        $declaredRoleNames = collect($this->catalog->roles())->map(fn (Role $role) => $role->value)->all();
        $declaredPermissionNames = $this->permissionNames($this->catalog->permissions());

        $existingRoleNames = PermissionRole::query()->pluck('name')->all();
        $existingPermissionNames = Permission::query()->pluck('name')->all();

        $rolesToCreate = array_values(array_diff($declaredRoleNames, $existingRoleNames));
        $permissionsToCreate = array_values(array_diff($declaredPermissionNames, $existingPermissionNames));
        $rolesToDelete = array_values(array_diff($existingRoleNames, $declaredRoleNames));
        $permissionsToDelete = array_values(array_diff($existingPermissionNames, $declaredPermissionNames));

        $permissionsToAttachByRole = [];
        $permissionsToDetachByRole = [];

        foreach ($this->catalog->roles() as $role) {
            $permissionsForRole = $this->permissionNames($this->catalog->permissionsFor($role));
            $existingRolePermissions = PermissionRole::query()->where('name', $role->value)->first()?->permissions()->pluck('name')->all() ?? [];

            $permissionsToAttachByRole[$role->value] = array_values(array_diff($permissionsForRole, $existingRolePermissions));
            $permissionsToDetachByRole[$role->value] = array_values(array_diff($existingRolePermissions, $permissionsForRole));
        }

        return new AuthorizationSyncResult(
            rolesToCreate: $rolesToCreate,
            permissionsToCreate: $permissionsToCreate,
            rolesToDelete: $rolesToDelete,
            permissionsToDelete: $permissionsToDelete,
            permissionsToAttachByRole: $permissionsToAttachByRole,
            permissionsToDetachByRole: $permissionsToDetachByRole,
            dryRun: $dryRun,
        );
    }

    private function applyCatalog(): void
    {
        $declaredRoleNames = collect($this->catalog->roles())->map(fn (Role $role) => $role->value)->all();
        $declaredPermissionNames = $this->permissionNames($this->catalog->permissions());

        foreach ($this->catalog->permissions() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        foreach ($this->catalog->roles() as $role) {
            $permissionRole = PermissionRole::findOrCreate($role->value);

            $permissionsForRole = $this->permissionNames($this->catalog->permissionsFor($role));

            $permissionRole->syncPermissions($permissionsForRole);
        }

        PermissionRole::query()->whereNotIn('name', $declaredRoleNames)->get()->each(
            fn (PermissionRole $role) => $role->delete()
        );

        Permission::query()->whereNotIn('name', $declaredPermissionNames)->get()->each(
            fn (Permission $permission) => $permission->delete()
        );
    }

    /**
     * Extract the string values of the given permissions.
     *
     * @param  list<\App\Enums\Permission>  $permissions
     * @return list<string>
     */
    private function permissionNames(array $permissions): array
    {
        $names = [];

        foreach ($permissions as $permission) {
            $names[] = $permission->value;
        }

        return $names;
    }
}
