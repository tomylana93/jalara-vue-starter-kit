<?php

use App\Actions\UpdateUserProfile;
use App\Models\User;

test('it updates profile attributes', function () {
    $user = User::factory()->create();

    $updatedUser = (new UpdateUserProfile)->handle($user, [
        'name' => 'Updated User',
        'email' => 'updated@example.com',
        'phone' => '+628144444444',
    ]);

    expect($updatedUser->name)->toBe('Updated User')
        ->and($updatedUser->email)->toBe('updated@example.com')
        ->and($updatedUser->phone)->toBe('+628144444444');
});

test('it clears email verification when the email changes', function () {
    $user = User::factory()->create();

    (new UpdateUserProfile)->handle($user, [
        'name' => $user->name,
        'email' => 'changed@example.com',
        'phone' => null,
    ]);

    expect($user->refresh()->email_verified_at)->toBeNull();
});

test('it preserves email verification when the email is unchanged', function () {
    $user = User::factory()->create();

    (new UpdateUserProfile)->handle($user, [
        'name' => 'Updated User',
        'email' => $user->email,
        'phone' => null,
    ]);

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});
