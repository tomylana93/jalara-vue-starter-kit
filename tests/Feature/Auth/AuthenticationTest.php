<?php

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('login security policy exposes default configuration values', function () {
    expect(config('auth.login_security.max_failed_attempts'))->toBe(5)
        ->and(config('auth.login_security.suspension_minutes'))->toBe(15);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});

test('soft-deleted users can not authenticate', function () {
    $user = User::factory()->create();
    $user->delete();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    expect($user->fresh()?->failed_login_attempts)->toBe(0);
});

test('disabled users can not authenticate', function () {
    $user = User::factory()->create(['status' => UserStatus::Disable]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    expect($user->fresh()->failed_login_attempts)->toBe(0);
});

test('manually suspended users can not authenticate', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Suspend,
        'suspended_until' => null,
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    expect($user->fresh()->status)->toBe(UserStatus::Suspend)
        ->and($user->fresh()->failed_login_attempts)->toBe(0);
});

test('an active automatic suspension blocks authentication until it expires', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Suspend,
        'suspended_until' => now()->addMinutes(15),
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    expect($user->fresh()->status)->toBe(UserStatus::Suspend);
});

test('an incorrect password increments the failed-login counter', function () {
    Config::set('auth.login_security.max_failed_attempts', 5);

    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    expect($user->fresh()->failed_login_attempts)->toBe(1)
        ->and($user->fresh()->status)->toBe(UserStatus::Active);
});

test('reaching the failed-attempt threshold suspends the user', function () {
    Config::set('auth.login_security.max_failed_attempts', 2);
    Config::set('auth.login_security.suspension_minutes', 15);

    $this->freezeTime();

    $user = User::factory()->create();

    foreach (range(1, 2) as $attempt) {
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $this->assertGuest();
    $fresh = $user->fresh();
    expect($fresh->failed_login_attempts)->toBe(2)
        ->and($fresh->status)->toBe(UserStatus::Suspend)
        ->and($fresh->suspended_until)->not->toBeNull()
        ->and($fresh->suspended_until->format('Y-m-d H:i:s'))
        ->toBe(now()->addMinutes(15)->format('Y-m-d H:i:s'));
});

test('a completed password login records the last login timestamp', function () {
    $this->freezeTime();
    $user = User::factory()->create(['last_login_at' => null]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at->format('Y-m-d H:i:s'))
        ->toBe(now()->format('Y-m-d H:i:s'));
});

test('a failed login does not record the last login timestamp', function () {
    $user = User::factory()->create(['last_login_at' => null]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    expect($user->fresh()->last_login_at)->toBeNull();
});

test('an active user with the correct password authenticates', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('a successful login clears the failed-login counter', function () {
    $user = User::factory()->create(['failed_login_attempts' => 3]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->failed_login_attempts)->toBe(0);
});

test('ineligible accounts do not increment the failed-login counter', function (Closure $makeUser) {
    Config::set('auth.login_security.max_failed_attempts', 2);

    $user = $makeUser();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    expect($user->fresh()->failed_login_attempts)->toBe(0);
})->with([
    'soft-deleted' => fn () => tap(User::factory()->create(), fn (User $user) => $user->delete()),
    'disabled' => fn () => User::factory()->create(['status' => UserStatus::Disable]),
    'manually suspended' => fn () => User::factory()->create([
        'status' => UserStatus::Suspend,
        'suspended_until' => null,
    ]),
]);

test('stale failed login does not overwrite an administrator disable', function () {
    Config::set('auth.login_security.max_failed_attempts', 5);

    $user = User::factory()->create(['failed_login_attempts' => 4]);
    $changed = false;

    User::retrieved(function (User $retrieved) use ($user, &$changed): void {
        if (! $changed && $retrieved->is($user)) {
            $changed = true;

            User::withoutEvents(fn () => User::query()->whereKey($user)->update([
                'status' => UserStatus::Disable,
            ]));
        }
    });

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $fresh = $user->fresh();
    expect($fresh->status)->toBe(UserStatus::Disable)
        ->and($fresh->failed_login_attempts)->toBe(4)
        ->and($fresh->suspended_until)->toBeNull();
});

test('an expired automatic suspension is restored before authentication', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Suspend,
        'suspended_until' => now()->subMinute(),
        'failed_login_attempts' => 5,
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $fresh = $user->fresh();
    expect($fresh->status)->toBe(UserStatus::Active)
        ->and($fresh->suspended_until)->toBeNull()
        ->and($fresh->failed_login_attempts)->toBe(0);
});
