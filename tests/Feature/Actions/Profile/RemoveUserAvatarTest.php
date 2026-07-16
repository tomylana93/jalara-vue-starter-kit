<?php

use App\Actions\Profile\RemoveUserAvatar;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('it removes the users avatar media', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))->toMediaCollection('avatar');

    app(RemoveUserAvatar::class)->handle($user);

    expect($user->refresh()->hasMedia('avatar'))->toBeFalse();
});
