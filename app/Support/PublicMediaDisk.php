<?php

namespace App\Support;

final class PublicMediaDisk
{
    public static function name(): string
    {
        $r2 = config('filesystems.disks.r2');

        if (! is_array($r2)) {
            return 'public';
        }

        foreach (['key', 'secret', 'bucket', 'endpoint', 'url'] as $key) {
            if (blank($r2[$key] ?? null)) {
                return 'public';
            }
        }

        return 'r2';
    }
}
