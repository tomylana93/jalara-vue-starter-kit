<?php

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('factory produces a valid default user', function () {
    $user = User::factory()->create();

    expect($user->getKey())->toBeString()
        ->and($user->status)->toBe(UserStatus::Active)
        ->and($user->must_change_password)->toBeFalse()
        ->and($user->last_login_at)->toBeNull()
        ->and($user->phone)->toBeNull();
});

test('factory can create a user who must change password', function () {
    $user = User::factory()->mustChangePassword()->create();

    expect($user->must_change_password)->toBeTrue();
});
