<?php

namespace App\Support;

final class MediaDisk
{
    public static function avatar(): string
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
