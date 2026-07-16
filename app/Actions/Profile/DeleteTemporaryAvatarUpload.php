<?php

namespace App\Actions\Profile;

use App\Models\TemporaryAvatarUpload;
use App\Models\User;

final class DeleteTemporaryAvatarUpload
{
    /**
     * Delete an owned temporary avatar upload, reporting the outcome.
     */
    public function handle(User $user, string $uploadId): TemporaryAvatarDeletionResult
    {
        $upload = TemporaryAvatarUpload::query()->find($uploadId);

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
