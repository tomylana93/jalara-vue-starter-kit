<?php

use App\Actions\Profile\PurgeExpiredAvatarUploads;
use App\Models\TemporaryAvatarUpload;
use Illuminate\Support\Facades\Storage;

test('it deletes only expired uploads and returns the deleted count', function () {
    $this->freezeTime();
    Storage::fake('public');
    $expired = TemporaryAvatarUpload::factory()->expired()->create();
    $expiresNow = TemporaryAvatarUpload::factory()->create(['expires_at' => now()]);
    $unexpired = TemporaryAvatarUpload::factory()->create(['expires_at' => now()->addSecond()]);

    foreach ([$expired, $expiresNow, $unexpired] as $upload) {
        Storage::disk($upload->disk)->put($upload->path, 'contents');
    }

    $count = app(PurgeExpiredAvatarUploads::class)->handle();

    expect($count)->toBe(2)
        ->and($expired->fresh())->toBeNull()
        ->and($expiresNow->fresh())->toBeNull()
        ->and($unexpired->fresh())->not->toBeNull();
});
