<?php

use App\Models\TemporaryAvatarUpload;
use App\Models\User;
use App\Support\MediaDisk;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    expect(TemporaryAvatarUpload::query()->where('user_id', $user->id)->count())->toBe(1);
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
        ->and(TemporaryAvatarUpload::find($upload->id))->toBeNull();
});

test('a user cannot destroy or promote another users staged avatar', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $upload = stageAvatarFor($owner);

    $this->actingAs($attacker)
        ->deleteJson(route('profile.avatar-uploads.destroy', $upload))
        ->assertNotFound();

    $this->actingAs($attacker)
        ->patch(route('profile.update'), profilePayload($attacker, ['temporary_avatar_upload_id' => $upload->id]))
        ->assertSessionHasErrors('temporary_avatar_upload_id');
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

function stageAvatarFor(User $user): TemporaryAvatarUpload
{
    $file = UploadedFile::fake()->image('avatar.jpg');
    $disk = MediaDisk::avatar();
    $path = $file->storeAs("temporary-avatars/{$user->id}", Str::random(40).'.jpg', ['disk' => $disk]);

    return TemporaryAvatarUpload::create([
        'user_id' => $user->id,
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
