<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role as PermissionRole;

test('the protected super admin bypasses every Gate ability', function () {
    PermissionRole::findOrCreate(Role::SuperAdmin->value);
    $systemUser = User::factory()->create(['is_system' => true]);
    $systemUser->applySystemRole(Role::SuperAdmin);

    expect(Gate::forUser($systemUser)->allows('unregistered-ability'))->toBeTrue();
});
