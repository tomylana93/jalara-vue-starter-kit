<?php

it('provides profile and uploader copy in both locales', function (string $locale, array $expected): void {
    expect(__('profile.information.heading', [], $locale))->toBe($expected['heading'])
        ->and(__('profile.form.label.name', [], $locale))->toBe($expected['label_name'])
        ->and(__('profile.form.placeholder.name', [], $locale))->toBe($expected['placeholder_name'])
        ->and(__('profile.avatar.remove', [], $locale))->toBe($expected['remove_avatar'])
        ->and(__('profile.email_verification.resend_link', [], $locale))->toBe($expected['resend_link'])
        ->and(__('profile.action.save', [], $locale))->toBe($expected['save'])
        ->and(__('profile.avatar.uploader.idle', [], $locale))->toBe($expected['uploader_idle'])
        ->and(__('general.uploader.idle', [], $locale))->toBe($expected['generic_idle']);
})->with([
    'English' => ['en', [
        'heading' => 'Profile information',
        'label_name' => 'Name',
        'placeholder_name' => 'Full name',
        'remove_avatar' => 'Remove avatar',
        'resend_link' => 'Click here to re-send the verification email.',
        'save' => 'Save',
        'uploader_idle' => 'Drop your JPEG or WebP avatar here, or browse',
        'generic_idle' => 'Drop files here or browse',
    ]],
    'Indonesian' => ['id', [
        'heading' => 'Informasi profil',
        'label_name' => 'Nama',
        'placeholder_name' => 'Nama lengkap',
        'remove_avatar' => 'Hapus avatar',
        'resend_link' => 'Klik di sini untuk mengirim ulang email verifikasi.',
        'save' => 'Simpan',
        'uploader_idle' => 'Jatuhkan avatar JPEG atau WebP Anda di sini, atau telusuri',
        'generic_idle' => 'Jatuhkan berkas di sini atau telusuri',
    ]],
]);

it('migrates profile copy and uploader defaults to the typed translator', function (string $file, array $mustNotContain): void {
    $source = file_get_contents(resource_path("js/{$file}"));

    expect($source)->toContain('trans(');

    foreach ($mustNotContain as $needle) {
        expect($source)->not->toContain($needle);
    }
})->with([
    'Profile' => ['pages/Profile.vue', [
        'Profile information',
        'Remove avatar',
        'Full name',
        'Your email address is unverified.',
        'Drop your JPEG or WebP avatar here',
        '>Save<',
    ]],
    'Uploader' => ['components/uploader/Uploader.vue', []],
]);
