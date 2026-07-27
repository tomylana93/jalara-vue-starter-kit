<?php

use App\Enums\Role as AppRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('a system user cannot be deleted or have roles changed', function () {
    $systemUser = User::factory()->create(['is_system' => true]);

    expect(fn () => $systemUser->delete())->toThrow(LogicException::class)
        ->and(fn () => $systemUser->syncRoles([]))->toThrow(LogicException::class);
});

test('a system user cannot have roles changed via public mutations', function () {
    $systemUser = User::factory()->create(['is_system' => true]);
    $role = Role::findOrCreate('some-role');

    expect(fn () => $systemUser->assignRole($role))->toThrow(LogicException::class)
        ->and(fn () => $systemUser->removeRole($role))->toThrow(LogicException::class)
        ->and(fn () => $systemUser->syncRoles([$role]))->toThrow(LogicException::class);
});

test('a system user cannot have roles changed through its relationship', function () {
    $systemUser = User::factory()->create(['is_system' => true]);
    $role = Role::findOrCreate('some-role');

    expect(fn () => $systemUser->roles()->attach($role))->toThrow(LogicException::class)
        ->and(fn () => $systemUser->roles()->detach($role))->toThrow(LogicException::class)
        ->and(fn () => $systemUser->roles()->sync([$role]))->toThrow(LogicException::class);
});

test('a non-system user cannot enforce the super admin role', function () {
    $user = User::factory()->create();
    Role::findOrCreate(AppRole::SuperAdmin->value);

    expect(fn () => $user->enforceSuperAdminRole())->toThrow(LogicException::class);
});

test('a system user cannot be made non-system through a model update', function () {
    $systemUser = User::factory()->create(['is_system' => true]);

    $systemUser->is_system = false;

    expect(fn () => $systemUser->save())->toThrow(LogicException::class)
        ->and($systemUser->fresh()->isSystem())->toBeTrue();
});
