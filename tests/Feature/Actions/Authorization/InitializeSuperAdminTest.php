<?php

use App\Actions\Authorization\InitializeSuperAdmin;
use App\Enums\Role;
use App\Enums\UserStatus;
use Illuminate\Support\Facades\Hash;

/**
 * @return array{name: string, email: string, phone: ?string, status: UserStatus, email_verified: bool, password: ?string}
 */
function superAdminAttributes(
    bool $emailVerified = true,
    ?string $password = 'super-secret-password',
): array {
    return [
        'name' => 'Super Admin',
        'email' => 'superadmin@example.com',
        'phone' => '+621234567890',
        'status' => UserStatus::Active,
        'email_verified' => $emailVerified,
        'password' => $password,
    ];
}

test('it creates and restores the protected super admin', function () {
    $action = app(InitializeSuperAdmin::class);
    $user = $action->handle(superAdminAttributes(), false);

    expect($user->is_system)->toBeTrue()
        ->and($user->hasRole(Role::SuperAdmin))->toBeTrue();

    $user->deleteQuietly();
    $restored = $action->handle(superAdminAttributes(), false);

    expect($restored->id)->toBe($user->id)
        ->and($restored->trashed())->toBeFalse();
});

test('it only resets an existing password when requested', function () {
    $action = app(InitializeSuperAdmin::class);
    $user = $action->handle(superAdminAttributes(password: 'initial-password'), false);

    $user->update(['password' => 'preserved-password']);
    $action->handle(superAdminAttributes(password: 'replacement-password'), false);
    expect(Hash::check('preserved-password', $user->fresh()->password))->toBeTrue();

    $action->handle(superAdminAttributes(password: 'replacement-password'), true);
    expect(Hash::check('replacement-password', $user->fresh()->password))->toBeTrue();
});

test('it reconciles the email verification state', function () {
    $action = app(InitializeSuperAdmin::class);

    $verified = $action->handle(superAdminAttributes(emailVerified: true), false);
    expect($verified->email_verified_at)->not->toBeNull();

    $unverified = $action->handle(superAdminAttributes(emailVerified: false), false);
    expect($unverified->fresh()->email_verified_at)->toBeNull();
});
