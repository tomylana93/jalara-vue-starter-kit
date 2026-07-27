<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('guests are redirected from the root route to the login page', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});

test('authenticated users are redirected from the root route to the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertRedirect(route('dashboard'));
});

test('authenticated users are redirected from the root route to the intended destination', function () {
    $user = User::factory()->create();
    $intendedUrl = route('profile.edit', absolute: false);

    $response = $this->actingAs($user)
        ->withSession(['url.intended' => $intendedUrl])
        ->get(route('home'));

    $response->assertRedirect($intendedUrl);
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
