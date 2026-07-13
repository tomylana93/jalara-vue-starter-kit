<?php

use App\Authorization\AuthorizationCatalog;
use App\Enums\Role;

test('the initial catalog declares only the super admin role', function () {
    $catalog = app(AuthorizationCatalog::class);

    expect($catalog->roles())->toBe([Role::SuperAdmin])
        ->and($catalog->permissions())->toBe([])
        ->and($catalog->permissionsFor(Role::SuperAdmin))->toBe([]);
});

test('the testing super admin password defaults to password', function () {
    expect(config('superadmin.password'))->toBe('password');
});
