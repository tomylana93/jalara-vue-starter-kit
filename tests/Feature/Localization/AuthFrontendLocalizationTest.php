<?php

it('provides authentication Vue copy in both locales', function (string $locale, array $expected): void {
    expect(__('auth.login.card.heading', [], $locale))->toBe($expected['login_heading'])
        ->and(__('auth.login.action.submit', [], $locale))->toBe($expected['login_submit'])
        ->and(__('auth.forgot_password.card.description', [], $locale))->toBe($expected['forgot_description'])
        ->and(__('auth.verify_email.action.resend', [], $locale))->toBe($expected['verify_resend'])
        ->and(__('auth.two_factor_challenge.authentication_code.title', [], $locale))->toBe($expected['tfa_code_title'])
        ->and(__('auth.two_factor_challenge.recovery_code.title', [], $locale))->toBe($expected['tfa_recovery_title'])
        ->and(__('auth.confirm_password.passkey.label', [], $locale))->toBe($expected['confirm_passkey'])
        ->and(__('auth.common.action.continue', [], $locale))->toBe($expected['continue'])
        ->and(__('auth.common.aria.otp', [], $locale))->toBe($expected['otp_aria']);
})->with([
    'English' => ['en', [
        'login_heading' => 'Log in to your account',
        'login_submit' => 'Log in',
        'forgot_description' => 'Enter your email to receive a password reset link',
        'verify_resend' => 'Resend verification email',
        'tfa_code_title' => 'Authentication code',
        'tfa_recovery_title' => 'Recovery code',
        'confirm_passkey' => 'Confirm with passkey',
        'continue' => 'Continue',
        'otp_aria' => 'One-time authentication code',
    ]],
    'Indonesian' => ['id', [
        'login_heading' => 'Masuk ke akun Anda',
        'login_submit' => 'Masuk',
        'forgot_description' => 'Masukkan email Anda untuk menerima tautan reset kata sandi',
        'verify_resend' => 'Kirim ulang email verifikasi',
        'tfa_code_title' => 'Kode autentikasi',
        'tfa_recovery_title' => 'Kode pemulihan',
        'confirm_passkey' => 'Konfirmasi dengan passkey',
        'continue' => 'Lanjutkan',
        'otp_aria' => 'Kode autentikasi sekali pakai',
    ]],
]);

it('migrates authentication pages and layouts to the typed translator', function (string $file, array $mustNotContain): void {
    $source = file_get_contents(resource_path("js/{$file}"));

    expect($source)->toContain('trans(');

    foreach ($mustNotContain as $needle) {
        expect($source)->not->toContain($needle);
    }
})->with([
    'Login' => ['pages/auth/Login.vue', ['>Email address<', 'Remember me', 'Forgot your password?', 'title="Log in"']],
    'ForgotPassword' => ['pages/auth/ForgotPassword.vue', ['Email password reset link', 'Or, return to', 'title="Forgot password"']],
    'ResetPassword' => ['pages/auth/ResetPassword.vue', ['title="Reset password"', '>Confirm password <']],
    'ConfirmPassword' => ['pages/auth/ConfirmPassword.vue', ['Confirm with passkey', 'Confirming...', 'title="Confirm password"']],
    'VerifyEmail' => ['pages/auth/VerifyEmail.vue', ['Resend verification email', 'title="Email verification"']],
    'TwoFactorChallenge' => ['pages/auth/TwoFactorChallenge.vue', ['Authentication code', 'Recovery code', 'Enter recovery code', 'title="Two-factor authentication"']],
    'AuthSimpleLayout' => ['layouts/auth/AuthSimpleLayout.vue', []],
    'AuthCardLayout' => ['layouts/auth/AuthCardLayout.vue', []],
    'AuthSplitLayout' => ['layouts/auth/AuthSplitLayout.vue', []],
]);
