<?php

use App\Authorization\AuthorizationCatalog;
use App\Enums\Permission;
use App\Enums\Role;

test('the catalog declares the super admin role and its permissions', function () {
    $catalog = app(AuthorizationCatalog::class);

    expect($catalog->roles())->toBe([Role::SuperAdmin])
        ->and($catalog->permissions())->toBe([Permission::ManageSettings])
        ->and($catalog->permissionsFor(Role::SuperAdmin))->toBe([Permission::ManageSettings])
        ->and(new ReflectionMethod($catalog, 'permissionsFor')->getParameters())->toHaveCount(1);
});

test('the testing super admin password defaults to password', function () {
    expect(config('superadmin.password'))->toBe('password');
});
