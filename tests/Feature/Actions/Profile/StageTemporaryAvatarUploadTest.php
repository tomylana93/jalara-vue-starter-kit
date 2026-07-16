<?php

use App\Actions\Profile\StageTemporaryAvatarUpload;
use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use App\Models\User;
use App\Support\MediaDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->freezeTime();
    Storage::fake('public');
});

test('it stores a temporary avatar and records its metadata', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg');

    $upload = app(StageTemporaryAvatarUpload::class)->handle($user, $file);

    expect($upload->user_id)->toBe($user->id)
        ->and($upload->disk)->toBe(MediaDisk::avatar())
        ->and($upload->purpose)->toBe(TemporaryUploadPurpose::Avatar)
        ->and($upload->original_name)->toBe('avatar.jpg')
        ->and($upload->expires_at->equalTo(now()->addDay()->startOfSecond()))->toBeTrue();
    Storage::disk($upload->disk)->assertExists($upload->path);
});

test('it removes the stored file when record creation fails', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg');
    TemporaryUpload::creating(fn () => throw new RuntimeException('database unavailable'));

    expect(fn () => app(StageTemporaryAvatarUpload::class)->handle($user, $file))
        ->toThrow(RuntimeException::class, 'database unavailable');

    expect(Storage::disk(MediaDisk::avatar())->allFiles("temporary-uploads/{$user->id}/avatar"))->toBeEmpty();
});
