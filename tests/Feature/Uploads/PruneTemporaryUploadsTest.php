<?php

use App\Actions\Uploads\PruneTemporaryUploads;
use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use Illuminate\Support\Facades\Storage;

test('it prunes uploads of every purpose older than the retention period', function () {
    $this->freezeTime();
    Storage::fake('public');
    $expired = TemporaryUpload::factory()->create(['purpose' => TemporaryUploadPurpose::Avatar, 'created_at' => now()->subHours(25)]);
    $expiresNow = TemporaryUpload::factory()->create(['purpose' => TemporaryUploadPurpose::Branding, 'created_at' => now()->subHours(24)]);
    $unexpired = TemporaryUpload::factory()->create(['created_at' => now()->subHours(23)]);

    foreach ([$expired, $expiresNow, $unexpired] as $upload) {
        Storage::disk($upload->disk)->put($upload->path, 'contents');
    }

    $count = app(PruneTemporaryUploads::class)->handle();

    expect($count)->toBe(2)
        ->and($expired->fresh())->toBeNull()
        ->and($expiresNow->fresh())->toBeNull()
        ->and($unexpired->fresh())->not->toBeNull();
});
