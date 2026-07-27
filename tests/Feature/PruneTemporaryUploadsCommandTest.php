<?php

use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->freezeTime();
    Storage::fake('public');
    Storage::fake('local');
});

test('it prunes temporary uploads older than 24 hours and their stored files', function () {
    $expiredUpload = TemporaryUpload::factory()->create([
        'purpose' => TemporaryUploadPurpose::Avatar,
        'disk' => 'public',
        'path' => 'temporary-avatars/expired.jpg',
        'expires_at' => now()->subSecond(),
        'created_at' => now()->subHours(25),
    ]);
    $expiresNowUpload = TemporaryUpload::factory()->create([
        'purpose' => TemporaryUploadPurpose::Avatar,
        'disk' => 'local',
        'path' => 'temporary-avatars/expires-now.webp',
        'expires_at' => now(),
        'created_at' => now()->subHours(24),
    ]);
    $unexpiredUpload = TemporaryUpload::factory()->create([
        'purpose' => TemporaryUploadPurpose::Avatar,
        'disk' => 'public',
        'path' => 'temporary-avatars/unexpired.jpg',
        'expires_at' => now()->addSecond(),
        'created_at' => now()->subHours(23),
    ]);

    Storage::disk('public')->put($expiredUpload->path, 'expired');
    Storage::disk('local')->put($expiresNowUpload->path, 'expires now');
    Storage::disk('public')->put($unexpiredUpload->path, 'unexpired');

    $this->artisan('uploads:prune-temporary --hours=24')
        ->assertExitCode(0);

    expect($expiredUpload->fresh())->toBeNull()
        ->and($expiresNowUpload->fresh())->toBeNull()
        ->and($unexpiredUpload->fresh())->not->toBeNull();

    Storage::disk('public')->assertMissing($expiredUpload->path);
    Storage::disk('local')->assertMissing($expiresNowUpload->path);
    Storage::disk('public')->assertExists($unexpiredUpload->path);
});

test('it is idempotent when an expired upload file is already missing', function () {
    $upload = TemporaryUpload::factory()->create([
        'purpose' => TemporaryUploadPurpose::Avatar,
        'disk' => 'local',
        'path' => 'temporary-avatars/missing.webp',
        'expires_at' => now()->subSecond(),
        'created_at' => now()->subHours(25),
    ]);

    $this->artisan('uploads:prune-temporary --hours=24')
        ->assertExitCode(0);

    expect($upload->fresh())->toBeNull();
});

test('it retains an expired upload when its stored file cannot be deleted', function () {
    $upload = TemporaryUpload::factory()->create([
        'purpose' => TemporaryUploadPurpose::Avatar,
        'disk' => 'public',
        'path' => 'temporary-avatars/undeletable.webp',
        'expires_at' => now()->subSecond(),
        'created_at' => now()->subHours(25),
    ]);

    $disk = mock(FilesystemAdapter::class);
    $disk->shouldReceive('delete')->once()->with($upload->path)->andReturnFalse();
    $disk->shouldReceive('missing')->once()->with($upload->path)->andReturnFalse();

    Storage::shouldReceive('disk')->once()->with($upload->disk)->andReturn($disk);

    expect(fn () => $this->artisan('uploads:prune-temporary --hours=24'))
        ->toThrow(RuntimeException::class, 'Unable to delete temporary upload file.');

    expect($upload->fresh())->not->toBeNull();
});

test('temporary upload pruning is scheduled hourly without overlapping', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'uploads:prune-temporary --hours=24'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 * * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});
