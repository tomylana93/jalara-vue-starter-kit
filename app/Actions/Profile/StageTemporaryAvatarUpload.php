<?php

namespace App\Actions\Profile;

use App\Models\TemporaryAvatarUpload;
use App\Models\User;
use App\Support\MediaDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class StageTemporaryAvatarUpload
{
    /**
     * Store a temporary avatar upload and record its metadata.
     */
    public function handle(User $user, UploadedFile $file): TemporaryAvatarUpload
    {
        $disk = MediaDisk::avatar();

        $path = $file->store("temporary-avatars/{$user->id}", ['disk' => $disk]);

        try {
            return TemporaryAvatarUpload::query()->create([
                'user_id' => $user->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'expires_at' => now()->addDay(),
            ]);
        } catch (Throwable $throwable) {
            Storage::disk($disk)->delete($path);

            throw $throwable;
        }
    }
}
