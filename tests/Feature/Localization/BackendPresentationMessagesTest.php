<?php

use App\Enums\SiteLocale;
use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use App\Models\User;
use App\Settings\GeneralSettings;

it('translates backend presentation messages', function (string $locale, array $expected): void {
    app()->setLocale($locale);

    expect(__('profile.toast.updated'))->toBe($expected['profile_updated'])
        ->and(__('profile.toast.avatar_removed'))->toBe($expected['avatar_removed'])
        ->and(__('profile.error.temporary_avatar_invalid'))->toBe($expected['avatar_invalid'])
        ->and(__('profile.error.temporary_avatar_unavailable'))->toBe($expected['avatar_unavailable'])
        ->and(__('security.password.toast.updated'))->toBe($expected['password_updated']);
})->with([
    'English' => ['en', [
        'profile_updated' => 'Profile updated.',
        'avatar_removed' => 'Avatar removed.',
        'avatar_invalid' => 'The temporary avatar upload is invalid or has expired.',
        'avatar_unavailable' => 'We could not access your staged avatar. Please try again.',
        'password_updated' => 'Password updated.',
    ]],
    'Indonesian' => ['id', [
        'profile_updated' => 'Profil diperbarui.',
        'avatar_removed' => 'Avatar dihapus.',
        'avatar_invalid' => 'Unggahan avatar sementara tidak valid atau telah kedaluwarsa.',
        'avatar_unavailable' => 'Kami tidak dapat mengakses avatar yang Anda siapkan. Silakan coba lagi.',
        'password_updated' => 'Kata sandi diperbarui.',
    ]],
]);

it('resolves translated locale endonyms independently of the active locale', function (string $locale): void {
    app()->setLocale($locale);

    expect(SiteLocale::options())
        ->toContain(['value' => 'en', 'label' => 'English'])
        ->toContain(['value' => 'id', 'label' => 'Bahasa Indonesia']);
})->with(['en', 'id']);

it('supplies framework validation and password messages in both locales', function (): void {
    expect(__('validation.email', ['attribute' => 'email'], 'en'))
        ->toBe('The email field must be a valid email address.')
        ->and(__('validation.email', ['attribute' => 'email'], 'id'))
        ->toBe('Kolom email harus berupa alamat email yang valid.')
        ->and(__('validation.required', ['attribute' => 'name'], 'id'))
        ->toBe('Kolom name wajib diisi.')
        ->and(__('passwords.sent', [], 'id'))
        ->toBe('Kami telah mengirim tautan reset kata sandi Anda melalui email.')
        ->and(__('passwords.throttled', [], 'id'))
        ->toBe('Silakan tunggu sebelum mencoba lagi.');
});

it('keeps authentication throttle and failure lines translated', function (): void {
    expect(__('auth.failed', [], 'id'))
        ->toBe('Kredensial ini tidak cocok dengan data kami.')
        ->and(__('auth.throttle', ['seconds' => 30], 'id'))
        ->toBe('Terlalu banyak percobaan masuk. Silakan coba lagi dalam 30 detik.');
});

it('returns the invalid staged avatar message in the configured locale', function (): void {
    $settings = app(GeneralSettings::class);
    $settings->site_locale = SiteLocale::Indonesian->value;
    $settings->save();

    $user = User::factory()->create();
    $upload = TemporaryUpload::factory()->create([
        'user_id' => $user->id,
        'purpose' => TemporaryUploadPurpose::Avatar,
        'expires_at' => now()->subSecond(),
    ]);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'temporary_avatar_upload_id' => $upload->id,
        ])
        ->assertSessionHasErrors([
            'temporary_avatar_upload_id' => 'Unggahan avatar sementara tidak valid atau telah kedaluwarsa.',
        ]);
});
