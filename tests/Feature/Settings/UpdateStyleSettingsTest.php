<?php

use App\Actions\Settings\UpdateStyleSettings;
use App\Actions\Uploads\StageTemporaryUpload;
use App\Data\StyleSettingsPayload;
use App\Enums\Permission;
use App\Enums\TemporaryUploadPurpose;
use App\Models\SiteBranding;
use App\Models\TemporaryUpload;
use App\Models\User;
use App\Settings\StyleSettings;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function (): void {
    Storage::fake('public');
    Queue::fake();
    $this->withoutMiddleware(PreventRequestForgery::class);
    $this->artisan('auth:sync-authorization');
});

test('style save promotes an owned branding upload to its collection', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::ManageSettings->value);

    $upload = app(StageTemporaryUpload::class)->handle(
        $user,
        UploadedFile::fake()->image('icon.png'),
        TemporaryUploadPurpose::Branding,
        'icon',
    );

    $this->actingAs($user)->patch('/settings/style', [
        ...stylePayload(),
        'icon_upload_id' => $upload->id,
    ])->assertRedirect('/settings/style');

    expect(SiteBranding::singleton()->getMedia(SiteBranding::Icon))->toHaveCount(1)
        ->and($upload->fresh())->toBeNull();
});

test('a staged branding upload cannot be promoted into a different field', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::ManageSettings->value);

    $upload = app(StageTemporaryUpload::class)->handle(
        $user,
        UploadedFile::fake()->image('logo.png'),
        TemporaryUploadPurpose::Branding,
        'logo',
    );

    $this->actingAs($user)->patch('/settings/style', [
        ...stylePayload(),
        'icon_upload_id' => $upload->id,
    ])->assertSessionHasErrors('icon_upload_id');

    expect(SiteBranding::singleton()->getMedia(SiteBranding::Icon))->toBeEmpty()
        ->and($upload->fresh())->not->toBeNull();
});

test('style save removes only the explicitly selected branding collection', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::ManageSettings->value);

    $branding = SiteBranding::singleton();
    $branding->addMedia(UploadedFile::fake()->image('icon.png'))
        ->toMediaCollection(SiteBranding::Icon);
    $branding->addMedia(UploadedFile::fake()->image('logo.png'))
        ->toMediaCollection(SiteBranding::Logo);

    $this->actingAs($user)->patch('/settings/style', [
        ...stylePayload(),
        'icon_remove' => true,
    ])->assertRedirect('/settings/style');

    expect($branding->fresh()->getMedia(SiteBranding::Icon))->toHaveCount(0)
        ->and($branding->fresh()->getMedia(SiteBranding::Logo))->toHaveCount(1);
});

test('a later promotion failure preserves settings and existing media while compensating new media', function (): void {
    $user = User::factory()->create();
    $branding = SiteBranding::singleton();
    $branding->addMedia(UploadedFile::fake()->image('old-icon.png'))
        ->toMediaCollection(SiteBranding::Icon);
    $branding->addMedia(UploadedFile::fake()->image('old-logo.png'))
        ->toMediaCollection(SiteBranding::Logo);

    $uploads = collect(['icon', 'logo', 'favicon'])->mapWithKeys(fn (string $field): array => [
        $field => app(StageTemporaryUpload::class)->handle(
            $user,
            UploadedFile::fake()->image("{$field}.png"),
            TemporaryUploadPurpose::Branding,
            $field,
        ),
    ]);
    $creating = 0;
    Media::creating(function () use (&$creating): void {
        $creating++;

        throw_if($creating === 3, RuntimeException::class, 'object storage unavailable');
    });

    expect(fn () => app(UpdateStyleSettings::class)->handle(app(StyleSettings::class), StyleSettingsPayload::fromArray([
        ...stylePayload(),
        'site_theme' => 'rose',
        'icon_upload_id' => $uploads['icon']->id,
        'logo_upload_id' => $uploads['logo']->id,
        'favicon_upload_id' => $uploads['favicon']->id,
    ]), $user))->toThrow(RuntimeException::class, 'object storage unavailable');

    expect(app(StyleSettings::class)->site_theme)->toBe('zinc')
        ->and($branding->fresh()->getMedia(SiteBranding::Icon))->toHaveCount(1)
        ->and($branding->fresh()->getFirstMedia(SiteBranding::Icon)?->file_name)->toBe('old-icon.png')
        ->and($branding->fresh()->getMedia(SiteBranding::Logo))->toHaveCount(1)
        ->and($branding->fresh()->getFirstMedia(SiteBranding::Logo)?->file_name)->toBe('old-logo.png')
        ->and(Media::query()->count())->toBe(2)
        ->and(TemporaryUpload::query()->whereKey($uploads['icon']->id)->exists())->toBeTrue();
});

/** @return array<string, string> */
function stylePayload(): array
{
    return [
        'site_logo_style' => 'icon',
        'site_auth_layout' => 'simple',
        'site_layout' => 'sidebar',
        'site_theme' => 'zinc',
        'site_font' => 'inter',
    ];
}
