<?php

namespace App\Actions;

use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use App\Models\User;
use finfo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class PromoteTemporaryAvatarUpload
{
    private const int MaximumAvatarSize = 2 * 1024 * 1024;

    /**
     * Validate and retrieve the user's staged avatar upload.
     *
     * @throws ValidationException
     */
    public function validate(User $user, ?string $temporaryAvatarUploadId): ?TemporaryUpload
    {
        if ($temporaryAvatarUploadId === null) {
            return null;
        }

        $upload = TemporaryUpload::query()
            ->where('id', $temporaryAvatarUploadId)
            ->where('user_id', $user->id)
            ->where('purpose', TemporaryUploadPurpose::Avatar->value)
            ->first();

        if ($upload === null || $upload->expires_at->isPast() || ! $this->hasValidStoredFile($upload)) {
            throw ValidationException::withMessages([
                'temporary_avatar_upload_id' => [__('profile.error.temporary_avatar_invalid')],
            ]);
        }

        return $upload;
    }

    /**
     * Promote a temporary avatar upload to the user's media collection.
     *
     * @throws ValidationException
     */
    public function handle(User $user, ?string $temporaryAvatarUploadId): void
    {
        $upload = $this->validate($user, $temporaryAvatarUploadId);

        if (! $upload instanceof TemporaryUpload) {
            return;
        }

        $user->addMediaFromDisk($upload->path, $upload->disk)
            ->usingFileName($upload->original_name)
            ->toMediaCollection('avatar');

        $upload->delete();
    }

    private function hasValidStoredFile(TemporaryUpload $upload): bool
    {
        try {
            $disk = Storage::disk($upload->disk);

            if (! $disk->exists($upload->path) || $disk->size($upload->path) > self::MaximumAvatarSize) {
                return false;
            }

            $mimeType = new finfo(FILEINFO_MIME_TYPE)->buffer($disk->get($upload->path));

            return in_array($mimeType, ['image/jpeg', 'image/webp'], true);
        } catch (Throwable $throwable) {
            report($throwable);

            throw $throwable;
        }
    }
}
