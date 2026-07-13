<?php

use App\Support\MediaDisk;
use Tests\TestCase;

uses(TestCase::class);

test('avatar media uses the public disk when R2 is incomplete', function () {
    config()->set('filesystems.disks.r2', [
        'key' => null,
        'secret' => null,
        'bucket' => null,
        'endpoint' => null,
        'url' => null,
    ]);

    expect(MediaDisk::avatar())->toBe('public');
});

test('avatar media uses R2 only when every required setting is present', function () {
    config()->set('filesystems.disks.r2', [
        'key' => 'key',
        'secret' => 'secret',
        'bucket' => 'bucket',
        'endpoint' => 'https://account.r2.cloudflarestorage.com',
        'url' => 'https://cdn.example.test',
    ]);

    expect(MediaDisk::avatar())->toBe('r2');
});
