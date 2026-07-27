<?php

use App\Enums\SiteAuthLayout;
use App\Enums\SiteFont;
use App\Enums\SiteLayout;
use App\Enums\SiteLogoStyle;
use App\Enums\SiteTheme;
use App\Settings\StyleSettings;

test('it provides the approved default style settings', function (): void {
    $settings = app(StyleSettings::class);

    expect($settings->site_logo_style)->toBe(SiteLogoStyle::Icon->value)
        ->and($settings->site_auth_layout)->toBe(SiteAuthLayout::Simple->value)
        ->and($settings->site_layout)->toBe(SiteLayout::Sidebar->value)
        ->and($settings->site_theme)->toBe(SiteTheme::Zinc->value)
        ->and($settings->site_font)->toBe(SiteFont::Inter->value);
});

test('it exposes the complete approved style option matrix', function (): void {
    expect(array_column(SiteLogoStyle::options(), 'value'))->toBe(['icon', 'logo'])
        ->and(array_column(SiteAuthLayout::options(), 'value'))->toBe(['simple', 'split', 'card'])
        ->and(array_column(SiteLayout::options(), 'value'))->toBe(['sidebar', 'header'])
        ->and(array_column(SiteTheme::options(), 'value'))->toBe([
            'zinc', 'slate', 'emerald', 'rose', 'indigo',
            'violet', 'cyan', 'orange', 'teal', 'fuchsia',
        ])->and(array_column(SiteFont::options(), 'value'))->toBe([
            'inter', 'sora-inter', 'plus-jakarta-dm-sans',
            'space-grotesk-inter', 'nunito-plus-jakarta',
        ]);
});

test('it shares style and branding with every inertia response', function (): void {
    $this->get('/login')
        ->assertSee('data-theme="zinc"', false)
        ->assertSee('data-font="inter"', false)
        ->assertSee('href="/assets/images/branding/favicon.ico"', false)
        ->assertInertia(fn ($page) => $page
            ->where('style.site_logo_style', 'icon')
            ->where('style.site_auth_layout', 'simple')
            ->where('style.site_layout', 'sidebar')
            ->where('style.site_theme', 'zinc')
            ->where('style.site_font', 'inter')
            ->where('branding.auth_split_background', '/assets/images/auth-bg.jpg'));
});
