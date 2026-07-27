<?php

use App\Enums\Permission;
use App\Models\User;
use App\Settings\GeneralSettings;

it('renders primary flows in the configured locale', function (string $locale, array $copy): void {
    $settings = app(GeneralSettings::class);
    $settings->site_locale = $locale;
    $settings->save();

    visit(route('login'))
        ->assertSee($copy['login'])
        ->assertSee($copy['login_heading'])
        ->assertDontSee('auth.login.action.submit')
        ->assertDontSee('auth.login.card.heading')
        ->assertNoSmoke();

    $this->artisan('auth:sync-authorization');
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::ManageSettings->value);
    $this->actingAs($user);
    $this->withSession(['auth.password_confirmed_at' => time()]);

    visit(route('profile.edit'))
        ->assertSee($copy['profile'])
        ->assertDontSee('profile.information.heading')
        ->assertNoSmoke();

    visit(route('security.edit'))
        ->assertSee($copy['security'])
        ->assertDontSee('security.password.heading')
        ->assertNoSmoke();

    visit(route('settings.general.edit'))
        ->assertSee($copy['settings'])
        ->assertDontSee('settings.general.heading')
        ->assertNoSmoke();
})->with([
    'English' => ['en', [
        'login' => 'Log in',
        'login_heading' => 'Log in to your account',
        'profile' => 'Profile settings',
        'security' => 'Security settings',
        'settings' => 'General',
    ]],
    'Indonesian' => ['id', [
        'login' => 'Masuk',
        'login_heading' => 'Masuk ke akun Anda',
        'profile' => 'Pengaturan profil',
        'security' => 'Pengaturan keamanan',
        'settings' => 'Umum',
    ]],
]);
