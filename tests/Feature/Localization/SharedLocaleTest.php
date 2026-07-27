<?php

use App\Enums\SiteLocale;
use App\Models\User;
use App\Settings\GeneralSettings;

use function Pest\Laravel\actingAs;

test('the active locale is shared with inertia responses', function (): void {
    $settings = app(GeneralSettings::class);
    $settings->site_locale = SiteLocale::Indonesian->value;
    $settings->save();

    $user = User::factory()->create();

    actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('locale', SiteLocale::Indonesian->value));
});
