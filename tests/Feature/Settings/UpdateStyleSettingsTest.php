<?php

use App\Actions\Uploads\StageTemporaryUpload;
use App\Enums\Permission;
use App\Enums\TemporaryUploadPurpose;
use App\Models\SiteBranding;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

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
    );

    $this->actingAs($user)->patch('/settings/style', [
        ...stylePayload(),
        'icon_upload_id' => $upload->id,
    ])->assertRedirect('/settings/style');

    expect(SiteBranding::singleton()->getMedia(SiteBranding::Icon))->toHaveCount(1)
        ->and($upload->fresh())->toBeNull();
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
