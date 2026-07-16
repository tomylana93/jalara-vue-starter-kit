<?php

namespace App\Actions\Profile;

use App\Models\TemporaryUpload;

final class PurgeExpiredAvatarUploads
{
    /**
     * Delete expired temporary avatar uploads and return the number removed.
     */
    public function handle(): int
    {
        $deleted = 0;

        TemporaryUpload::query()
            ->where('expires_at', '<=', now())
            ->cursor()
            ->each(function (TemporaryUpload $upload) use (&$deleted): void {
                $upload->delete();
                $deleted++;
            });

        return $deleted;
    }
}
