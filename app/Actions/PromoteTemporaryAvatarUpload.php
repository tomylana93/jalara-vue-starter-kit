<?php

namespace App\Actions;

use App\Models\TemporaryAvatarUpload;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PromoteTemporaryAvatarUpload
{
    /**
     * Promote a temporary avatar upload to the user's media collection.
     *
     * @throws ValidationException
     */
    public function handle(User $user, ?string $temporaryAvatarUploadId): void
    {
        if ($temporaryAvatarUploadId === null) {
            return;
        }

        $upload = TemporaryAvatarUpload::query()
            ->where('id', $temporaryAvatarUploadId)
            ->where('user_id', $user->id)
            ->first();

        if (! $upload || $upload->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'temporary_avatar_upload_id' => [__('The temporary avatar upload is invalid or has expired.')],
            ]);
        }

        $user->addMediaFromDisk($upload->path, $upload->disk)
            ->usingFileName($upload->original_name)
            ->toMediaCollection('avatar');

        $upload->delete();
    }
}
