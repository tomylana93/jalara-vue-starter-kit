<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
        ->get(route('profile.edit'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Profile')
            ->where('auth.user.name', $user->name)
            ->where('auth.user.email', $user->email)
            ->where('auth.user.phone', '+628111111111')
        );
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
