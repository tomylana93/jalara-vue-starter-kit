<?php

use App\Support\PublicMediaDisk;
use Tests\TestCase;

uses(TestCase::class);

test('it falls back to public when R2 is incomplete', function (): void {
    config()->set('filesystems.disks.r2', [
        'key' => 'key',
        'secret' => 'secret',
        'bucket' => null,
        'endpoint' => 'https://account.r2.cloudflarestorage.com',
        'url' => 'https://cdn.example.test',
    ]);

    expect(PublicMediaDisk::name())->toBe('public');
});

test('it selects R2 only when every public setting is present', function (): void {
    config()->set('filesystems.disks.r2', [
        'key' => 'key',
        'secret' => 'secret',
        'bucket' => 'bucket',
        'endpoint' => 'https://account.r2.cloudflarestorage.com',
        'url' => 'https://cdn.example.test',
    ]);

    expect(PublicMediaDisk::name())->toBe('r2');
});
