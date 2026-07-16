<?php

use App\Models\SiteBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it resolves one stable site branding owner', function (): void {
    $first = SiteBranding::singleton();
    $second = SiteBranding::singleton();

    expect($first->is($second))->toBeTrue()
        ->and(SiteBranding::query()->count())->toBe(1);
});

test('its branding collections replace their single file', function (): void {
    Storage::fake('public');
    $branding = SiteBranding::singleton();

    $branding->addMedia(UploadedFile::fake()->image('first.png'))
        ->toMediaCollection(SiteBranding::Icon);
    $branding->addMedia(UploadedFile::fake()->image('second.png'))
        ->toMediaCollection(SiteBranding::Icon);

    expect($branding->fresh()->getMedia(SiteBranding::Icon))->toHaveCount(1);
});

test('its background collection rejects unsupported files', function (): void {
    Storage::fake('public');
    $branding = SiteBranding::singleton();

    expect(fn () => $branding
        ->addMedia(UploadedFile::fake()->create('background.png', 100, 'image/png'))
        ->toMediaCollection(SiteBranding::AuthSplitBackground))
        ->toThrow(FileCannotBeAdded::class);
});
