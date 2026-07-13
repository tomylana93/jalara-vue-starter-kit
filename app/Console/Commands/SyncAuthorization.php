<?php

namespace App\Console\Commands;

use App\Authorization\AuthorizationCatalog;
use App\Enums\Role;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as PermissionRole;
use Spatie\Permission\PermissionRegistrar;

#[Signature('auth:sync-authorization {--dry-run}')]
#[Description('Synchronize the roles and permissions tables with the authorization catalog.')]
class SyncAuthorization extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AuthorizationCatalog $catalog): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $declaredRoleNames = collect($catalog->roles())->map(fn (Role $role) => $role->value)->all();

        $declaredPermissionNames = $this->permissionNames($catalog->permissions());

        $existingRoleNames = PermissionRole::query()->pluck('name')->all();
        $existingPermissionNames = Permission::query()->pluck('name')->all();

        $rolesToCreate = array_values(array_diff($declaredRoleNames, $existingRoleNames));
        $permissionsToCreate = array_values(array_diff($declaredPermissionNames, $existingPermissionNames));
        $rolesToDelete = array_values(array_diff($existingRoleNames, $declaredRoleNames));
        $permissionsToDelete = array_values(array_diff($existingPermissionNames, $declaredPermissionNames));

        if ($isDryRun) {
            $this->components->info('Dry run: no records will be modified.');
            $this->components->twoColumnDetail('Roles to create', $rolesToCreate === [] ? 'none' : implode(', ', $rolesToCreate));
            $this->components->twoColumnDetail('Permissions to create', $permissionsToCreate === [] ? 'none' : implode(', ', $permissionsToCreate));
            $this->components->twoColumnDetail('Roles to delete', $rolesToDelete === [] ? 'none' : implode(', ', $rolesToDelete));
            $this->components->twoColumnDetail('Permissions to delete', $permissionsToDelete === [] ? 'none' : implode(', ', $permissionsToDelete));

            foreach ($catalog->roles() as $role) {
                $permissionsForRole = $this->permissionNames($catalog->permissionsFor($role));
                $existingPermissionNames = PermissionRole::query()->where('name', $role->value)->first()?->permissions()->pluck('name')->all() ?? [];

                $permissionsToAttach = array_values(array_diff($permissionsForRole, $existingPermissionNames));
                $permissionsToDetach = array_values(array_diff($existingPermissionNames, $permissionsForRole));

                $this->components->twoColumnDetail(
                    "Permissions to attach to [{$role->value}]",
                    $permissionsToAttach === [] ? 'none' : implode(', ', $permissionsToAttach)
                );

                $this->components->twoColumnDetail(
                    "Permissions to detach from [{$role->value}]",
                    $permissionsToDetach === [] ? 'none' : implode(', ', $permissionsToDetach)
                );
            }

            return self::SUCCESS;
        }

        foreach ($catalog->permissions() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        foreach ($catalog->roles() as $role) {
            $permissionRole = PermissionRole::findOrCreate($role->value);

            $permissionsForRole = $this->permissionNames($catalog->permissionsFor($role));

            $permissionRole->syncPermissions($permissionsForRole);
        }

        PermissionRole::query()->whereNotIn('name', $declaredRoleNames)->get()->each(
            fn (PermissionRole $role) => $role->delete()
        );

        Permission::query()->whereNotIn('name', $declaredPermissionNames)->get()->each(
            fn (Permission $permission) => $permission->delete()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->components->info('Authorization catalog synchronized.');

        return self::SUCCESS;
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
