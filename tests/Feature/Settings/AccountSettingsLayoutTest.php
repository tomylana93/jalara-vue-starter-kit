<?php

test('the account settings layout has accessible wayfinder navigation', function (): void {
    $layout = file_get_contents(resource_path('js/layouts/settings/Layout.vue'));

    expect($layout)
        ->toContain("from '@/routes/profile'")
        ->toContain("from '@/routes/security'")
        ->toContain(':aria-current=')
        ->toContain("'page'")
        ->not->toContain("import Heading from '@/components/Heading.vue'")
        ->not->toContain('<component :is="item.icon"');
});

test('account settings navigation copy is translated', function (string $locale, array $expected): void {
    expect(__('settings.layout.aria', [], $locale))->toBe($expected['aria'])
        ->and(__('settings.nav.profile', [], $locale))->toBe($expected['profile'])
        ->and(__('settings.nav.security', [], $locale))->toBe($expected['security'])
        ->and(__('settings.profile.heading', [], $locale))->toBe($expected['profile_heading'])
        ->and(__('settings.security.heading', [], $locale))->toBe($expected['security_heading']);
})->with([
    'English' => ['en', [
        'aria' => 'Account settings',
        'profile' => 'Profile',
        'security' => 'Security',
        'profile_heading' => 'Profile settings',
        'security_heading' => 'Security settings',
    ]],
    'Indonesian' => ['id', [
        'aria' => 'Pengaturan akun',
        'profile' => 'Profil',
        'security' => 'Keamanan',
        'profile_heading' => 'Pengaturan profil',
        'security_heading' => 'Pengaturan keamanan',
    ]],
]);
