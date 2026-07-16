<?php

namespace App\Actions\Uploads;

use App\Models\TemporaryUpload;

final class PruneTemporaryUploads
{
    /**
     * Delete temporary uploads older than the retention period.
     */
    public function handle(int $hours = 24): int
    {
        $deleted = 0;

        TemporaryUpload::query()
            ->where('created_at', '<=', now()->subHours($hours))
            ->cursor()
            ->each(function (TemporaryUpload $upload) use (&$deleted): void {
                $upload->delete();
                $deleted++;
            });

        return $deleted;
    }
}
