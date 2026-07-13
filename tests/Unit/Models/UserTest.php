<?php

use App\Enums\UserStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('users use uuid string keys and soft deletes', function () {
    $user = User::factory()->create();

    expect($user->getKey())->toBeString()
        ->and($user->getIncrementing())->toBeFalse()
        ->and($user->getKeyType())->toBe('string');

    $user->delete();

    expect(User::withTrashed()->find($user->getKey()))->not->toBeNull()
        ->and(User::query()->find($user->getKey()))->toBeNull();
});

test('users cast new attributes', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Suspend,
        'must_change_password' => true,
        'last_login_at' => now(),
    ]);

    expect($user->status)->toBe(UserStatus::Suspend)
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->last_login_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('users cast the login-security attributes', function () {
    $user = User::factory()->create([
        'failed_login_attempts' => '3',
        'suspended_until' => now(),
    ]);

    expect($user->failed_login_attempts)->toBeInt()
        ->and($user->failed_login_attempts)->toBe(3)
        ->and($user->suspended_until)->toBeInstanceOf(CarbonImmutable::class);
});

test('a user avatar collection is single-file and exposes its conversion URL', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))
        ->toMediaCollection('avatar');

    expect($user->getMedia('avatar'))->toHaveCount(1)
        ->and($user->avatarUrl())->toBe($user->getFirstMediaUrl('avatar', 'avatar'));
});
