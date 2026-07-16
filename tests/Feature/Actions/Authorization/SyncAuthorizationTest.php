<?php

use App\Actions\Authorization\SyncAuthorization;
use App\Authorization\AuthorizationCatalog;
use App\Enums\Permission as PermissionEnum;
use App\Enums\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as PermissionRole;
use Spatie\Permission\PermissionRegistrar;

test('it reports and applies the authorization catalog diff', function () {
    PermissionRole::findOrCreate('obsolete-role');
    Permission::findOrCreate('obsolete.permission');

    $result = app(SyncAuthorization::class)->handle();

    expect($result->dryRun)->toBeFalse()
        ->and($result->rolesToDelete)->toContain('obsolete-role')
        ->and($result->permissionsToDelete)->toContain('obsolete.permission')
        ->and(PermissionRole::findByName(Role::SuperAdmin->value)
            ->hasPermissionTo(PermissionEnum::ManageSettings->value))->toBeTrue()
        ->and(PermissionRole::query()->where('name', 'obsolete-role')->exists())->toBeFalse();
});

test('dry run reports the diff without mutating records or permission cache', function () {
    PermissionRole::findOrCreate('obsolete-role');
    $registrar = mock(PermissionRegistrar::class);
    $registrar->shouldNotReceive('forgetCachedPermissions');
    app()->instance(PermissionRegistrar::class, $registrar);

    $action = new SyncAuthorization(app(AuthorizationCatalog::class), $registrar);
    $result = $action->handle(dryRun: true);

    expect($result->dryRun)->toBeTrue()
        ->and($result->rolesToDelete)->toContain('obsolete-role')
        ->and(PermissionRole::query()->where('name', 'obsolete-role')->exists())->toBeTrue();
});
