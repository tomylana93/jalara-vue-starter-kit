<?php

use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use App\Models\User;
use App\Support\MediaDisk;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    Storage::fake('public');
});

test('an authenticated user can stage a JPEG avatar', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('profile.avatar-uploads.store'), [
            'file' => UploadedFile::fake()->image('avatar.jpg'),
        ])
        ->assertCreated()
        ->assertJsonStructure(['id', 'name', 'size', 'type']);

    expect(TemporaryUpload::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('avatar uploads can be staged via the direct profile URL', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/profile/avatar-uploads', ['file' => UploadedFile::fake()->image('avatar.jpg')])
        ->assertCreated();
});

test('staged avatars reject unsupported types and files larger than two MiB', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('profile.avatar-uploads.store'), ['file' => UploadedFile::fake()->create('avatar.png', 100, 'image/png')])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');

    $this->actingAs($user)
        ->postJson(route('profile.avatar-uploads.store'), ['file' => UploadedFile::fake()->create('avatar.webp', 2049, 'image/webp')])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

test('a profile save promotes only the current users staged avatar', function () {
    $user = User::factory()->create();
    $upload = stageAvatarFor($user);

    $this->actingAs($user)
        ->patch(route('profile.update'), profilePayload($user, ['temporary_avatar_upload_id' => $upload->id]))
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->getMedia('avatar'))->toHaveCount(1)
        ->and(TemporaryUpload::query()->find($upload->id))->toBeNull();
});

test('a user cannot destroy or promote another users staged avatar', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $upload = stageAvatarFor($owner);

    $this->actingAs($attacker)
        ->deleteJson(route('profile.avatar-uploads.destroy', $upload))
        ->assertNotFound();

    $this->actingAs($attacker)
        ->patch(route('profile.update'), profilePayload($attacker, [
            'name' => 'Changed Name',
            'temporary_avatar_upload_id' => $upload->id,
        ]))
        ->assertSessionHasErrors('temporary_avatar_upload_id');

    expect($attacker->refresh()->name)->not->toBe('Changed Name');
});

test('a new avatar replaces the old avatar and the owner can remove it', function () {
    $user = User::factory()->create();
    $user->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('avatar');
    $upload = stageAvatarFor($user);

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, ['temporary_avatar_upload_id' => $upload->id]));
    expect($user->refresh()->getMedia('avatar'))->toHaveCount(1);

    $this->actingAs($user)
        ->delete(route('profile.avatar.destroy'))
        ->assertRedirect(route('profile.edit'));
    expect($user->refresh()->hasMedia('avatar'))->toBeFalse();
});

test('unauthenticated users cannot stage avatars', function () {
    $this->postJson(route('profile.avatar-uploads.store'), [
        'file' => UploadedFile::fake()->image('avatar.jpg'),
    ])
        ->assertUnauthorized();

    $this->post(route('profile.avatar-uploads.store'), [
        'file' => UploadedFile::fake()->image('avatar.jpg'),
    ])
        ->assertRedirect(route('login'));
});

test('unauthenticated users cannot destroy staged avatars', function () {
    $uuid = (string) Str::uuid();

    $this->deleteJson(route('profile.avatar-uploads.destroy', $uuid))
        ->assertUnauthorized();

    $this->delete(route('profile.avatar-uploads.destroy', $uuid))
        ->assertRedirect(route('login'));
});

test('unauthenticated users cannot delete profile avatar', function () {
    $this->deleteJson(route('profile.avatar.destroy'))
        ->assertUnauthorized();

    $this->delete(route('profile.avatar.destroy'))
        ->assertRedirect(route('login'));
});

test('an owner can successfully destroy their staged upload and its file', function () {
    $user = User::factory()->create();
    $upload = stageAvatarFor($user);

    Storage::disk($upload->disk)->assertExists($upload->path);

    $this->actingAs($user)
        ->deleteJson(route('profile.avatar-uploads.destroy', $upload))
        ->assertNoContent();

    expect(TemporaryUpload::query()->find($upload->id))->toBeNull();
    Storage::disk($upload->disk)->assertMissing($upload->path);
});

test('an owner can cancel the same staged upload more than once', function () {
    $user = User::factory()->create();
    $upload = stageAvatarFor($user);

    $this->actingAs($user)
        ->deleteJson(route('profile.avatar-uploads.destroy', $upload))
        ->assertNoContent();

    $this->actingAs($user)
        ->deleteJson(route('profile.avatar-uploads.destroy', $upload->id))
        ->assertNoContent();
});

test('promoting an expired upload fails validation without updating the profile', function () {
    $user = User::factory()->create();
    $upload = TemporaryUpload::factory()->create([
        'user_id' => $user->id,
        'purpose' => TemporaryUploadPurpose::Avatar,
        'expires_at' => now()->subSecond(),
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), profilePayload($user, [
            'name' => 'Changed Name',
            'temporary_avatar_upload_id' => $upload->id,
        ]))
        ->assertSessionHasErrors([
            'temporary_avatar_upload_id' => 'The temporary avatar upload is invalid or has expired.',
        ]);

    expect($user->refresh()->name)->not->toBe('Changed Name');
});

test('promoting a missing staged file fails validation without updating the profile', function () {
    $user = User::factory()->create();
    $upload = stageAvatarFor($user);
    Storage::disk($upload->disk)->delete($upload->path);

    $this->actingAs($user)
        ->patch(route('profile.update'), profilePayload($user, [
            'name' => 'Changed Name',
            'temporary_avatar_upload_id' => $upload->id,
        ]))
        ->assertSessionHasErrors('temporary_avatar_upload_id');

    expect($user->refresh()->name)->not->toBe('Changed Name');
});

test('a storage read failure while promoting an avatar returns a retryable form error without updating the profile', function () {
    $user = User::factory()->create();
    $upload = stageAvatarFor($user);

    Storage::shouldReceive('disk')
        ->once()
        ->with($upload->disk)
        ->andThrow(new RuntimeException('R2 is unavailable.'));

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), profilePayload($user, [
            'name' => 'Changed Name',
            'temporary_avatar_upload_id' => $upload->id,
        ]))
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors([
            'temporary_avatar_upload_id' => 'We could not access your staged avatar. Please try again.',
        ]);

    expect($user->refresh()->name)->not->toBe('Changed Name');
});

test('promoting a staged file with an invalid physical MIME type fails validation', function () {
    $user = User::factory()->create();
    $upload = stageAvatarFor($user);
    Storage::disk($upload->disk)->put($upload->path, 'not an image');

    $this->actingAs($user)
        ->patch(route('profile.update'), profilePayload($user, ['temporary_avatar_upload_id' => $upload->id]))
        ->assertSessionHasErrors('temporary_avatar_upload_id');
});

test('promoting a staged file larger than two MiB fails validation', function () {
    $user = User::factory()->create();
    $upload = stageAvatarFor($user);
    Storage::disk($upload->disk)->put($upload->path, str_repeat('a', 2 * 1024 * 1024 + 1));

    $this->actingAs($user)
        ->patch(route('profile.update'), profilePayload($user, ['temporary_avatar_upload_id' => $upload->id]))
        ->assertSessionHasErrors('temporary_avatar_upload_id');
});

test('staged files are removed from storage upon promotion', function () {
    $user = User::factory()->create();
    $upload = stageAvatarFor($user);

    Storage::disk($upload->disk)->assertExists($upload->path);

    $this->actingAs($user)
        ->patch(route('profile.update'), profilePayload($user, ['temporary_avatar_upload_id' => $upload->id]))
        ->assertRedirect(route('profile.edit'));

    Storage::disk($upload->disk)->assertMissing($upload->path);
});

test('the profile page shares the avatar conversion with the user shell', function () {
    $user = User::factory()->create();
    $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))
        ->toMediaCollection('avatar');

    $avatar = $user->refresh()->getFirstMedia('avatar');
    $conversionProperties = getimagesize($avatar->getPath('avatar'));

    expect($avatar->hasGeneratedConversion('avatar'))->toBeTrue()
        ->and($conversionProperties[0])->toBe(256)
        ->and($conversionProperties[1])->toBe(256)
        ->and($conversionProperties[2])->toBe(IMAGETYPE_WEBP);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile')
            ->where('auth.user.avatar', $user->refresh()->getFirstMediaUrl('avatar', 'avatar'))
            ->where('avatar.id', $user->getFirstMedia('avatar')->id)
            ->where('avatar.source', $user->getFirstMediaUrl('avatar', 'avatar'))
        );
});

test('the profile page and user shell share null avatar values after removal', function () {
    $user = User::factory()->create();
    $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))
        ->toMediaCollection('avatar');

    $this->actingAs($user)
        ->delete(route('profile.avatar.destroy'))
        ->assertRedirect(route('profile.edit'));

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile')
            ->where('auth.user.avatar', null)
            ->where('avatar', null)
        );
});

function stageAvatarFor(User $user): TemporaryUpload
{
    $file = UploadedFile::fake()->image('avatar.jpg');
    $disk = MediaDisk::avatar();
    $path = $file->storeAs("temporary-avatars/{$user->id}", Str::random(40).'.jpg', ['disk' => $disk]);

    return TemporaryUpload::query()->create([
        'user_id' => $user->id,
        'purpose' => TemporaryUploadPurpose::Avatar,
        'disk' => $disk,
        'path' => $path,
        'original_name' => 'avatar.jpg',
        'mime_type' => 'image/jpeg',
        'size' => $file->getSize(),
        'expires_at' => now()->addDay(),
    ]);
}

function profilePayload(User $user, array $overrides = []): array
{
    return array_merge([
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone,
    ], $overrides);
}
