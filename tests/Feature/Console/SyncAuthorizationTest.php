<?php

use App\Enums\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as PermissionRole;

test('the authorization sync creates declared roles and prunes undeclared entries', function () {
    PermissionRole::findOrCreate('obsolete-role');
    Permission::findOrCreate('obsolete.permission');

    $this->artisan('auth:sync-authorization')->assertSuccessful();

    expect(PermissionRole::findByName(Role::SuperAdmin->value))->not->toBeNull()
        ->and(PermissionRole::query()->where('name', 'obsolete-role')->exists())->toBeFalse()
        ->and(Permission::query()->where('name', 'obsolete.permission')->exists())->toBeFalse();
});

test('the authorization sync dry run does not mutate records', function () {
    PermissionRole::findOrCreate('obsolete-role');

    $this->artisan('auth:sync-authorization', ['--dry-run' => true])->assertSuccessful();

    expect(PermissionRole::query()->where('name', 'obsolete-role')->exists())->toBeTrue();
});
