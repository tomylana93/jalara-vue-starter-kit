<?php

test('the complete security page uses the page wrapper and account settings layout', function (): void {
    $securityPage = file_get_contents(resource_path('js/pages/Security.vue'));

    expect($securityPage)
        ->toContain("import PageWrapper from '@/components/PageWrapper.vue'")
        ->toContain("import SettingsLayout from '@/layouts/settings/Layout.vue'")
        ->toContain("trans('settings.security.title')")
        ->toContain("trans('settings.security.heading')")
        ->toContain("trans('settings.security.description')")
        ->toContain('<PageWrapper')
        ->toContain('<SettingsLayout>')
        ->toContain('<ManageTwoFactor')
        ->toContain('<ManagePasskeys')
        ->not->toContain('<h1 class="sr-only">Security settings</h1>');
});
