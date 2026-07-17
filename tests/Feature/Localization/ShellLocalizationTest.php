<?php

it('provides shell and navigation copy in both locales', function (string $locale, array $expected): void {
    expect(__('navigation.menu.label', [], $locale))->toBe($expected['menu'])
        ->and(__('navigation.platform.label', [], $locale))->toBe($expected['platform'])
        ->and(__('navigation.dashboard', [], $locale))->toBe($expected['dashboard'])
        ->and(__('navigation.profile', [], $locale))->toBe($expected['profile'])
        ->and(__('navigation.logout', [], $locale))->toBe($expected['logout'])
        ->and(__('general.appearance.change', [], $locale))->toBe($expected['appearance_change'])
        ->and(__('general.password.show', [], $locale))->toBe($expected['password_show'])
        ->and(__('general.password.hide', [], $locale))->toBe($expected['password_hide']);
})->with([
    'English' => ['en', [
        'menu' => 'Navigation menu',
        'platform' => 'Platform',
        'dashboard' => 'Dashboard',
        'profile' => 'Profile',
        'logout' => 'Log out',
        'appearance_change' => 'Change appearance',
        'password_show' => 'Show password',
        'password_hide' => 'Hide password',
    ]],
    'Indonesian' => ['id', [
        'menu' => 'Menu navigasi',
        'platform' => 'Platform',
        'dashboard' => 'Dasbor',
        'profile' => 'Profil',
        'logout' => 'Keluar',
        'appearance_change' => 'Ubah tampilan',
        'password_show' => 'Tampilkan kata sandi',
        'password_hide' => 'Sembunyikan kata sandi',
    ]],
]);

it('migrates shell components to the typed translator', function (string $file, array $mustContain, array $mustNotContain): void {
    $source = file_get_contents(resource_path("js/{$file}"));

    foreach ($mustContain as $needle) {
        expect($source)->toContain($needle);
    }

    foreach ($mustNotContain as $needle) {
        expect($source)->not->toContain($needle);
    }
})->with([
    'AppHeader' => ['components/AppHeader.vue', [
        "trans('navigation.menu.label')",
    ], ['Navigation menu', "'Dashboard'"]],
    'NavMain' => ['components/NavMain.vue', [
        "trans('navigation.platform.label')",
    ], ['>Platform<', 'Platform</']],
    'UserMenuContent' => ['components/UserMenuContent.vue', [
        "trans('navigation.profile')",
        "trans('navigation.logout')",
    ], ['Log out', '>Profile<']],
    'AppearanceToggle' => ['components/AppearanceToggle.vue', [
        "trans('general.appearance.change')",
    ], ['Change appearance', "label: 'Light'"]],
    'PasswordInput' => ['components/PasswordInput.vue', [
        "trans('general.password.show')",
        "trans('general.password.hide')",
    ], ["'Show password'", "'Hide password'"]],
    'Dashboard' => ['pages/Dashboard.vue', [
        "trans('navigation.dashboard')",
    ], ['title="Dashboard"']],
]);
