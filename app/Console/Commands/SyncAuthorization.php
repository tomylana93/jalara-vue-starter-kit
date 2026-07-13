<?php

namespace App\Console\Commands;

use App\Authorization\AuthorizationCatalog;
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

        $declaredRoleNames = collect($catalog->roles())->map(fn ($role) => $role->value)->all();
        $declaredPermissionNames = collect($catalog->permissions())->map(fn ($permission) => $permission->value)->all();

        $existingRoleNames = PermissionRole::pluck('name')->all();
        $existingPermissionNames = Permission::pluck('name')->all();

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
                $permissionsForRole = collect($catalog->permissionsFor($role))->map(fn ($permission) => $permission->value)->all();
                $this->components->twoColumnDetail(
                    "Permissions for role [{$role->value}]",
                    $permissionsForRole === [] ? 'none' : implode(', ', $permissionsForRole)
                );
            }

            return self::SUCCESS;
        }

        foreach ($catalog->permissions() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        foreach ($catalog->roles() as $role) {
            $permissionRole = PermissionRole::findOrCreate($role->value);

            $permissionsForRole = collect($catalog->permissionsFor($role))
                ->map(fn ($permission) => $permission->value)
                ->all();

            $permissionRole->syncPermissions($permissionsForRole);
        }

        PermissionRole::whereNotIn('name', $declaredRoleNames)->get()->each(
            fn (PermissionRole $role) => $role->delete()
        );

        Permission::whereNotIn('name', $declaredPermissionNames)->get()->each(
            fn (Permission $permission) => $permission->delete()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->components->info('Authorization catalog synchronized.');

        return self::SUCCESS;
    }
}
