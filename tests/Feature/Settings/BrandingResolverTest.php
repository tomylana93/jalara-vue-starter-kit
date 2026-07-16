<?php

use App\Models\SiteBranding;
use App\Support\Branding\BrandingResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('it resolves official fallbacks and an empty split background', function (): void {
    $resolved = app(BrandingResolver::class)->resolve(SiteBranding::singleton());

    expect($resolved)->toMatchArray([
        'icon' => '/assets/images/branding/icon.png',
        'icon_dark' => '/assets/images/branding/icon-dark.png',
        'logo' => '/assets/images/branding/logo.png',
        'logo_dark' => '/assets/images/branding/logo-dark.png',
        'favicon' => '/assets/images/branding/favicon.ico',
        'auth_split_background' => '/assets/images/auth-bg.jpg',
    ]);

    foreach (array_filter($resolved) as $asset) {
        expect(public_path(ltrim($asset, '/')))->toBeFile();
    }
});

test('it exposes an original while a branding conversion is pending', function (): void {
    Storage::fake('public');
    Queue::fake();
    $branding = SiteBranding::singleton();
    $media = $branding->addMedia(UploadedFile::fake()->image('icon.png'))
        ->toMediaCollection(SiteBranding::Icon);

    $resolved = app(BrandingResolver::class)->resolve($branding->refresh());

    expect($resolved['icon'])->toBe($media->getUrl());
});

test('custom auth split background media overrides the static fallback', function (): void {
    Storage::fake('public');
    Queue::fake();
    $branding = SiteBranding::singleton();
    $media = $branding->addMedia(UploadedFile::fake()->image('auth-bg.jpg'))
        ->toMediaCollection(SiteBranding::AuthSplitBackground);

    $resolved = app(BrandingResolver::class)->resolve($branding->refresh());

    expect($resolved['auth_split_background'])
        ->toBe($media->getUrl())
        ->not->toBe('/assets/images/auth-bg.jpg');
});
