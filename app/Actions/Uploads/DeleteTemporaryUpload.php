<?php

namespace App\Actions\Uploads;

use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use App\Models\User;

final class DeleteTemporaryUpload
{
    public function handle(User $user, string $uploadId, TemporaryUploadPurpose $purpose): bool
    {
        $upload = TemporaryUpload::query()
            ->whereKey($uploadId)
            ->whereBelongsTo($user)
            ->where('purpose', $purpose->value)
            ->first();

        if (! $upload instanceof TemporaryUpload) {
            return false;
        }

        $upload->delete();

        return true;
    }
}
