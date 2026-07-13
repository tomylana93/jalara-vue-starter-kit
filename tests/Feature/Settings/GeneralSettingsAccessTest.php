<?php

use App\Enums\Permission;
use App\Enums\SiteLocale;
use App\Models\User;
use App\Settings\GeneralSettings;

beforeEach(function (): void {
    $this->artisan('auth:sync-authorization');
});

test('a user without manage settings cannot open or update general settings', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('settings.index'))->assertForbidden();
    $this->actingAs($user)->get(route('settings.general.edit'))->assertForbidden();
    $this->actingAs($user)->patch(route('settings.general.update'), [])->assertForbidden();
});

test('a user with manage settings can view and update general settings', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::ManageSettings->value);

    $this->actingAs($user)->get(route('settings.index'))
        ->assertOk()->assertInertia(fn ($page) => $page->component('settings/Index'));
    $this->actingAs($user)->get(route('settings.general.edit'))
        ->assertOk()->assertInertia(fn ($page) => $page->component('settings/general/Edit'));

    $this->actingAs($user)->patch(route('settings.general.update'), [
        'site_name' => 'Configured Jalara',
        'site_description' => '',
        'site_locale' => SiteLocale::Indonesian->value,
    ])->assertRedirect(route('settings.general.edit'));

    expect(app(GeneralSettings::class)->site_name)->toBe('Configured Jalara')
        ->and(app(GeneralSettings::class)->site_description)->toBe('')
        ->and(app(GeneralSettings::class)->site_locale)->toBe(SiteLocale::Indonesian->value);
});

test('it validates the general settings payload', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::ManageSettings->value);

    $this->actingAs($user)->patch(route('settings.general.update'), [
        'site_name' => '',
        'site_description' => str_repeat('a', 1001),
        'site_locale' => 'fr',
    ])->assertSessionHasErrors(['site_name', 'site_description', 'site_locale']);
});
