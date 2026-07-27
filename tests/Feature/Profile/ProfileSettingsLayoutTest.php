<?php

test('the profile page uses the page wrapper and account settings layout', function (): void {
    $profilePage = file_get_contents(resource_path('js/pages/Profile.vue'));

    expect($profilePage)
        ->toContain("import PageWrapper from '@/components/PageWrapper.vue'")
        ->toContain("import SettingsLayout from '@/layouts/settings/Layout.vue'")
        ->toContain("trans('settings.profile.title')")
        ->toContain("trans('settings.profile.heading')")
        ->toContain("trans('settings.profile.description')")
        ->toContain('<PageWrapper')
        ->toContain('<SettingsLayout>')
        ->not->toContain('<h1 class="sr-only">Profile settings</h1>');
});
