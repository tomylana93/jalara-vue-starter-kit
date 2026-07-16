<?php

use App\Actions\Uploads\DeleteTemporaryUpload;
use App\Actions\Uploads\StageTemporaryUpload;
use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('it stages an owned upload with an explicit purpose', function (): void {
    Storage::fake('public');
    $user = User::factory()->create();

    $upload = app(StageTemporaryUpload::class)->handle(
        $user,
        UploadedFile::fake()->image('avatar.jpg'),
        TemporaryUploadPurpose::Avatar,
    );

    expect($upload->user->is($user))->toBeTrue()
        ->and($upload->purpose)->toBe(TemporaryUploadPurpose::Avatar)
        ->and($upload->expires_at->isFuture())->toBeTrue()
        ->and(Storage::disk('public')->exists($upload->path))->toBeTrue();
});

test('it only deletes a temporary upload for its owner and purpose', function (): void {
    Storage::fake('public');
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $upload = app(StageTemporaryUpload::class)->handle(
        $owner,
        UploadedFile::fake()->image('brand.jpg'),
        TemporaryUploadPurpose::Branding,
    );

    expect(app(DeleteTemporaryUpload::class)->handle(
        $stranger,
        $upload->id,
        TemporaryUploadPurpose::Branding,
    ))->toBeFalse()
        ->and(TemporaryUpload::query()->find($upload->id))->not->toBeNull()
        ->and(app(DeleteTemporaryUpload::class)->handle(
            $owner,
            $upload->id,
            TemporaryUploadPurpose::Avatar,
        ))->toBeFalse()
        ->and(app(DeleteTemporaryUpload::class)->handle(
            $owner,
            $upload->id,
            TemporaryUploadPurpose::Branding,
        ))->toBeTrue()
        ->and(TemporaryUpload::query()->find($upload->id))->toBeNull();
});
