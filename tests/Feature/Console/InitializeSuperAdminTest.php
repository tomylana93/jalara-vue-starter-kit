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
