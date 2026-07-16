<?php

namespace App\Actions\Profile;

use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use App\Models\User;

final class DeleteTemporaryAvatarUpload
{
    /**
     * Delete an owned temporary avatar upload, reporting the outcome.
     */
    public function handle(User $user, string $uploadId): TemporaryAvatarDeletionResult
    {
        $upload = TemporaryUpload::query()
            ->whereKey($uploadId)
            ->where('purpose', TemporaryUploadPurpose::Avatar->value)
            ->first();

        if ($upload === null) {
            return TemporaryAvatarDeletionResult::Missing;
        }

        if ($upload->user_id !== $user->id) {
            return TemporaryAvatarDeletionResult::Forbidden;
        }

        $upload->delete();

        return TemporaryAvatarDeletionResult::Deleted;
    }
}
