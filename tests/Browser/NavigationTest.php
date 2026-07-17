<?php

use App\Enums\Permission;
use App\Models\User;
use App\Settings\StyleSettings;

/**
 * Grants an authenticated user the `manage_settings` ability, mirroring the
 * setup used by LocalizationSmokeTest.
 */
function actingAsUserWithManageSettings(): User
{
    test()->artisan('auth:sync-authorization');

    $user = User::factory()->create();
    $user->givePermissionTo(Permission::ManageSettings->value);
    test()->actingAs($user);

    return $user;
}

/**
 * Switches the active shell to the header layout, mirroring how
 * LocalizationSmokeTest flips settings before visiting authenticated pages.
 */
function useHeaderLayout(): void
{
    $settings = app(StyleSettings::class);
    $settings->site_layout = 'header';
    $settings->save();
}

it('shows Dashboard and Settings leaves in the expanded sidebar for an authorized user', function (): void {
    actingAsUserWithManageSettings();

    visit(route('dashboard'))
        ->assertVisible('[data-testid="nav-item-dashboard"]')
        ->assertVisible('[data-testid="nav-item-settings"]')
        ->assertNoSmoke();
});

it('hides the Settings leaf in the expanded sidebar for an unauthorized user', function (): void {
    $this->artisan('auth:sync-authorization');
    $user = User::factory()->create();
    $this->actingAs($user);

    visit(route('dashboard'))
        ->assertVisible('[data-testid="nav-item-dashboard"]')
        ->assertMissing('[data-testid="nav-item-settings"]')
        ->assertNoSmoke();
});

it('shows a leaf tooltip when the sidebar is collapsed', function (): void {
    actingAsUserWithManageSettings();

    visit(route('dashboard'))
        ->click('[data-sidebar="trigger"]')
        ->hover('[data-testid="nav-item-dashboard"]')
        ->assertSeeIn('[role="tooltip"]', 'Dashboard')
        ->assertNoSmoke();
});

it('shows primary leaves in the desktop header layout and navigates on click', function (): void {
    useHeaderLayout();
    actingAsUserWithManageSettings();

    visit(route('dashboard'))
        ->assertVisible('[data-testid="nav-item-dashboard"]')
        ->assertVisible('[data-testid="nav-item-settings"]')
        ->click('[data-testid="nav-item-settings"]')
        ->assertPathIs('/settings')
        ->assertNoSmoke();
});

it('closes the mobile header sheet after selecting a primary leaf', function (): void {
    useHeaderLayout();
    actingAsUserWithManageSettings();

    visit(route('dashboard'))
        ->on()->mobile()
        ->click('[data-slot="sheet-trigger"]')
        ->assertVisible('[role="dialog"]')
        ->click('[role="dialog"] [data-testid="nav-item-dashboard"]')
        ->assertMissing('[role="dialog"]')
        ->assertNoSmoke();
});

it('closes the mobile header sheet after selecting a secondary link', function (): void {
    useHeaderLayout();
    actingAsUserWithManageSettings();

    visit(route('dashboard'))
        ->on()->mobile()
        ->click('[data-slot="sheet-trigger"]')
        ->assertVisible('[role="dialog"]')
        ->click('[role="dialog"] a[href="https://github.com/laravel/vue-starter-kit"]')
        ->assertMissing('[role="dialog"]')
        ->assertNoSmoke();
});

it('closes the mobile sidebar sheet after selecting a leaf', function (): void {
    actingAsUserWithManageSettings();

    visit(route('dashboard'))
        ->on()->mobile()
        ->click('[data-sidebar="trigger"]')
        ->assertVisible('[role="dialog"]')
        ->click('[data-testid="nav-item-settings"]')
        ->assertMissing('[role="dialog"]')
        ->assertNoSmoke();
});

it('closes an open mobile sidebar sheet when Escape is pressed', function (): void {
    actingAsUserWithManageSettings();

    visit(route('dashboard'))
        ->on()->mobile()
        ->click('[data-sidebar="trigger"]')
        ->assertVisible('[role="dialog"]')
        ->keys('[role="dialog"]', 'Escape')
        ->assertMissing('[role="dialog"]')
        ->assertNoSmoke();
});
