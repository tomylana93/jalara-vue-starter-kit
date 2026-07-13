<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('initialization preserves an existing system password unless reset is requested', function () {
    config()->set('superadmin.email', 'system@example.test');
    config()->set('superadmin.password', 'password');

    $this->artisan('auth:init-superadmin')->assertSuccessful();
    $systemUser = User::query()->where('email', 'system@example.test')->sole();
    $systemUser->update(['password' => 'changed-password']);

    $this->artisan('auth:init-superadmin')->assertSuccessful();

    expect(Hash::check('changed-password', $systemUser->fresh()->password))->toBeTrue();
});

test('initialization is idempotent', function () {
    config()->set('superadmin.email', 'system@example.test');
    config()->set('superadmin.password', 'password');

    $this->artisan('auth:init-superadmin')->assertSuccessful();
    $count = User::query()->count();

    $this->artisan('auth:init-superadmin')->assertSuccessful();

    expect(User::query()->count())->toBe($count);
});

test('initialization with reset-password option changes the password', function () {
    config()->set('superadmin.email', 'system@example.test');
    config()->set('superadmin.password', 'password');

    $this->artisan('auth:init-superadmin')->assertSuccessful();
    $systemUser = User::query()->where('email', 'system@example.test')->sole();

    config()->set('superadmin.password', 'new-password');
    $this->artisan('auth:init-superadmin', ['--reset-password' => true])->assertSuccessful();

    expect(Hash::check('new-password', $systemUser->fresh()->password))->toBeTrue();
});

test('initialization in production without a password fails', function () {
    config()->set('superadmin.email', 'system@example.test');
    config(['superadmin.password' => null]);

    $this->app['env'] = 'production';

    $this->artisan('auth:init-superadmin')->assertFailed();
});

test('initialization restores a trashed system user', function () {
    config()->set('superadmin.email', 'system@example.test');
    config()->set('superadmin.password', 'password');

    $this->artisan('auth:init-superadmin')->assertSuccessful();
    $systemUser = User::query()->where('email', 'system@example.test')->sole();

    $systemUser->deleteQuietly();

    $this->artisan('auth:init-superadmin')->assertSuccessful();

    expect($systemUser->fresh()->trashed())->toBeFalse();
});

test('initialization reconciles profile information', function () {
    config()->set('superadmin.email', 'system@example.test');
    config()->set('superadmin.password', 'password');
    config()->set('superadmin.name', 'Original Name');

    $this->artisan('auth:init-superadmin')->assertSuccessful();

    config()->set('superadmin.name', 'Updated Name');

    $this->artisan('auth:init-superadmin')->assertSuccessful();

    $systemUser = User::query()->where('email', 'system@example.test')->sole();

    expect($systemUser->name)->toBe('Updated Name');
});
