<?php

use App\Enums\Permission;
use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->withoutMiddleware(PreventRequestForgery::class);
    $this->artisan('auth:sync-authorization');
});

test('manage settings can stage and cancel a branding image', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::ManageSettings->value);

    $response = $this->actingAs($user)->postJson('/settings/style/uploads/auth_split_background', [
        'file' => UploadedFile::fake()->image('background.jpg', 1200, 800),
    ])->assertCreated();

    $upload = TemporaryUpload::query()->findOrFail($response->json('id'));

    expect($upload->purpose)->toBe(TemporaryUploadPurpose::Branding);

    $this->actingAs($user)
        ->deleteJson("/settings/style/uploads/{$upload->id}")
        ->assertNoContent();

    expect(TemporaryUpload::query()->find($upload->id))->toBeNull();
});

test('branding upload validates field-specific dimensions and permission', function (): void {
    $authorized = User::factory()->create();
    $authorized->givePermissionTo(Permission::ManageSettings->value);

    $this->actingAs($authorized)->postJson('/settings/style/uploads/auth_split_background', [
        'file' => UploadedFile::fake()->image('small.jpg', 600, 400),
    ])->assertUnprocessable()->assertJsonValidationErrors('file');

    $this->actingAs(User::factory()->create())->postJson('/settings/style/uploads/icon', [
        'file' => UploadedFile::fake()->image('icon.png'),
    ])->assertForbidden();
});
