<?php

namespace App\Support;

final class MediaDisk
{
    public static function avatar(): string
    {
        return PublicMediaDisk::name();
    }
}
