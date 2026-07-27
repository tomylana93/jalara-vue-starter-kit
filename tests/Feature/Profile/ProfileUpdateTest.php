<?php

use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use App\Models\User;
use App\Support\MediaDisk;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
});

test('profile page is displayed', function () {
    $user = User::factory()->create([
        'phone' => '+628111111111',
    ]);

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile')
            ->where('auth.user.name', $user->name)
            ->where('auth.user.email', $user->email)
            ->where('auth.user.phone', '+628111111111')
        );
});

test('profile email validation is deferred until form submission', function (): void {
    $profilePage = file_get_contents(resource_path('js/pages/Profile.vue'));

    expect($profilePage)
        ->not->toContain('type="email"')
        ->not->toContain("@blur=\"validate('email')\"");
});

test('legacy settings profile URL is unavailable', function () {
    $this->actingAs(User::factory()->create())
        ->get('/settings/profile')
        ->assertNotFound();
});

test('legacy settings and appearance URLs are unavailable', function (string $uri) {
    $this->actingAs(User::factory()->create())
        ->get($uri)
        ->assertNotFound();
})->with(['/settings/appearance']);

test('the appearance named route no longer exists', function () {
    expect(Route::has('appearance.edit'))->toBeFalse();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('profile information is updated when promoting a temporary avatar upload', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg');
    $disk = MediaDisk::avatar();
    $path = $file->storeAs("temporary-avatars/{$user->id}", Str::random(40).'.jpg', ['disk' => $disk]);
    $upload = TemporaryUpload::query()->create([
        'user_id' => $user->id,
        'purpose' => TemporaryUploadPurpose::Avatar,
        'disk' => $disk,
        'path' => $path,
        'original_name' => 'avatar.jpg',
        'mime_type' => 'image/jpeg',
        'size' => $file->getSize(),
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'phone' => '+628111111111',
            'temporary_avatar_upload_id' => $upload->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh())
        ->name->toBe('Updated User')
        ->email->toBe('updated@example.com')
        ->phone->toBe('+628111111111')
        ->getMedia('avatar')->toHaveCount(1);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('profile phone can be updated and persists', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => null,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '+628111111111',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->phone)->toBe('+628111111111');
    expect($user->email_verified_at)->not->toBeNull();
});

test('existing phone becomes null when phone is empty string', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '+628122222222',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->phone)->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
});

test('duplicate phone causes validation error and does not change user data', function () {
    $otherUser = User::factory()->create([
        'phone' => '+628133333333',
    ]);

    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
        'phone' => '+628111111111',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Attempted NameChange',
            'email' => 'attempted@example.com',
            'phone' => '+628133333333',
        ]);

    $response->assertSessionHasErrors('phone');

    $user->refresh();
    expect($user->name)->toBe('Original Name')
        ->and($user->email)->toBe('original@example.com')
        ->and($user->phone)->toBe('+628111111111');
});

test('user can update profile without changing their existing phone number', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '+628111111111',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'phone' => '+628111111111',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->name)->toBe('Updated Name');
});

test('profile precognition returns field validation errors without updating the user', function (): void {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $this->actingAs($user)
        ->withPrecognition()
        ->withHeader('Precognition-Validate-Only', 'name')
        ->patch(route('profile.update'), [
            'name' => '',
            'email' => $user->email,
            'phone' => '',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    expect($user->refresh())
        ->name->toBe('Original Name')
        ->email->toBe('original@example.com');
});

test('profile precognition succeeds without updating the user', function (): void {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $this->actingAs($user)
        ->withPrecognition()
        ->withHeader('Precognition-Validate-Only', 'name')
        ->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'phone' => '',
        ])
        ->assertSuccessfulPrecognition();

    expect($user->refresh()->name)->toBe('Original Name');
});

test('precognitive validation failures render as json instead of a redirect', function (): void {
    // Guards the bootstrap/app.php shouldRenderJsonWhen() predicate: this app
    // overrides the framework default to only render JSON for api/* or
    // expectsJson() requests, so precognitive validation failures would be
    // redirected (302) without the added isPrecognitive() branch, breaking the
    // Precognition client contract.
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withPrecognition()
        ->withHeader('Precognition-Validate-Only', 'name')
        ->patch(route('profile.update'), [
            'name' => '',
            'email' => $user->email,
            'phone' => '',
        ]);

    $response->assertUnprocessable();
    $response->assertHeader('content-type', 'application/json');

    expect($response->headers->get('location'))->toBeNull();
});
