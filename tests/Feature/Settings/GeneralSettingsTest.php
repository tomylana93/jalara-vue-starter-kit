<?php

use App\Enums\SiteLocale;
use App\Settings\GeneralSettings;

test('it provides the approved default general settings', function (): void {
    $settings = app(GeneralSettings::class);

    expect($settings->site_name)->toBe('Jalara Vue Starter Kit')
        ->and($settings->site_description)->toBe('Jalara provides a structured application foundation intended for projects that value modularity, flexibility, modern tooling, and clear organization.')
        ->and($settings->site_locale)->toBe(SiteLocale::English->value);
});
