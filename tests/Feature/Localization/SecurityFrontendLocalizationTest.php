<?php

it('provides security, two-factor, and passkey copy in both locales', function (string $locale, array $expected): void {
    expect(__('security.password.heading', [], $locale))->toBe($expected['password_heading'])
        ->and(__('security.password.label.current', [], $locale))->toBe($expected['current_password'])
        ->and(__('security.two_factor.heading', [], $locale))->toBe($expected['tfa_heading'])
        ->and(__('security.two_factor.action.enable', [], $locale))->toBe($expected['enable'])
        ->and(__('security.two_factor.recovery.title', [], $locale))->toBe($expected['recovery_title'])
        ->and(__('security.passkeys.empty.title', [], $locale))->toBe($expected['empty_title'])
        ->and(__('security.passkeys.form.label', [], $locale))->toBe($expected['passkey_name'])
        ->and(__('security.passkeys.remove.description', ['name' => 'Work'], $locale))->toBe($expected['remove_description'])
        ->and(__('security.passkeys.verify.label', [], $locale))->toBe($expected['verify_label']);
})->with([
    'English' => ['en', [
        'password_heading' => 'Update password',
        'current_password' => 'Current password',
        'tfa_heading' => 'Two-factor authentication',
        'enable' => 'Enable 2FA',
        'recovery_title' => '2FA recovery codes',
        'empty_title' => 'No passkeys yet',
        'passkey_name' => 'Passkey name',
        'remove_description' => 'Are you sure you want to remove the "Work" passkey? You will no longer be able to use it to sign in.',
        'verify_label' => 'Sign in with a passkey',
    ]],
    'Indonesian' => ['id', [
        'password_heading' => 'Perbarui kata sandi',
        'current_password' => 'Kata sandi saat ini',
        'tfa_heading' => 'Autentikasi dua faktor',
        'enable' => 'Aktifkan 2FA',
        'recovery_title' => 'Kode pemulihan 2FA',
        'empty_title' => 'Belum ada passkey',
        'passkey_name' => 'Nama passkey',
        'remove_description' => 'Apakah Anda yakin ingin menghapus passkey "Work"? Anda tidak akan dapat menggunakannya lagi untuk masuk.',
        'verify_label' => 'Masuk dengan passkey',
    ]],
]);

it('selects the passkey count plural branch in both locales', function (): void {
    expect(trans_choice('security.passkeys.count', 0, [], 'en'))->toBe('No passkeys')
        ->and(trans_choice('security.passkeys.count', 1, [], 'en'))->toBe('One passkey')
        ->and(trans_choice('security.passkeys.count', 3, ['count' => 3], 'en'))->toBe('3 passkeys')
        ->and(trans_choice('security.passkeys.count', 3, ['count' => 3], 'id'))->toBe('3 passkey');
});

it('migrates security components to the typed translator', function (string $file, array $mustNotContain): void {
    $source = file_get_contents(resource_path("js/{$file}"));

    expect($source)->toContain('trans(');

    foreach ($mustNotContain as $needle) {
        expect($source)->not->toContain($needle);
    }
})->with([
    'Security' => ['pages/Security.vue', ['Update password', 'Current password', '>Save<']],
    'ManageTwoFactor' => ['components/ManageTwoFactor.vue', ['Enable 2FA', 'Disable 2FA', 'Continue setup']],
    'TwoFactorSetupModal' => ['components/TwoFactorSetupModal.vue', ['Verify authentication code', 'or, enter the code manually', '>Back<', '>Confirm<']],
    'TwoFactorRecoveryCodes' => ['components/TwoFactorRecoveryCodes.vue', ['2FA recovery codes', 'Regenerate codes']],
    'ManagePasskeys' => ['components/ManagePasskeys.vue', ['No passkeys yet', 'passwordless sign-in']],
    'PasskeyRegister' => ['components/PasskeyRegister.vue', ['Add passkey', 'Passkey name', 'Register passkey']],
    'PasskeyItem' => ['components/PasskeyItem.vue', ['Remove passkey', '>Cancel<', 'Added ']],
    'PasskeyVerify' => ['components/PasskeyVerify.vue', ['Sign in with a passkey', 'Authenticating...', 'Or continue with email']],
]);
