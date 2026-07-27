<?php

use App\Enums\Role;
use App\Models\User;
use Spatie\Permission\Models\Role as PermissionRole;

test('a UUID user can receive the super admin role', function () {
    $user = User::factory()->create();
    PermissionRole::findOrCreate(Role::SuperAdmin->value);

    $user->assignRole(Role::SuperAdmin);

    expect($user->fresh()->hasRole(Role::SuperAdmin))->toBeTrue();
});
