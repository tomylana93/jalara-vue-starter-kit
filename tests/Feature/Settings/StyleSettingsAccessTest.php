<?php

use App\Enums\Permission;
use App\Models\User;
use App\Settings\StyleSettings;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

beforeEach(function (): void {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $this->artisan('auth:sync-authorization');
});

test('a user without manage settings cannot open or update style settings', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/settings/style')->assertForbidden();
    $this->actingAs($user)->patch('/settings/style', [])->assertForbidden();
});

test('a user with manage settings can view and update style settings', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::ManageSettings->value);

    $this->actingAs($user)->get('/settings/style')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/style/Edit')
            ->where('styleSettings.site_theme', 'zinc'));

    $this->actingAs($user)->patch('/settings/style', [
        'site_logo_style' => 'logo',
        'site_auth_layout' => 'split',
        'site_layout' => 'header',
        'site_theme' => 'teal',
        'site_font' => 'sora-inter',
    ])->assertRedirect('/settings/style');

    expect(app(StyleSettings::class)->site_logo_style)->toBe('logo')
        ->and(app(StyleSettings::class)->site_auth_layout)->toBe('split')
        ->and(app(StyleSettings::class)->site_layout)->toBe('header')
        ->and(app(StyleSettings::class)->site_theme)->toBe('teal')
        ->and(app(StyleSettings::class)->site_font)->toBe('sora-inter');
});

test('it validates every scalar style setting against its enum', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::ManageSettings->value);

    $this->actingAs($user)->patch('/settings/style', [
        'site_logo_style' => 'wordmark',
        'site_auth_layout' => 'full',
        'site_layout' => 'footer',
        'site_theme' => 'blue',
        'site_font' => 'comic-sans',
    ])->assertSessionHasErrors([
        'site_logo_style',
        'site_auth_layout',
        'site_layout',
        'site_theme',
        'site_font',
    ]);
});
