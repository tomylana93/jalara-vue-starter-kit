<?php

use App\Models\User;
use Illuminate\Support\Str;

test('uuid-backed users can authenticate, logout, and reject bad credentials', function () {
    $user = User::factory()->create();

    expect(Str::isUuid($user->getKey()))->toBeTrue();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'))->assertRedirect(route('home'));
    $this->assertGuest();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});
