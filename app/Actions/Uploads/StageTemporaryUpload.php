<?php

namespace App\Actions\Uploads;

use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use App\Models\User;
use App\Support\PublicMediaDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class StageTemporaryUpload
{
    public function handle(User $user, UploadedFile $file, TemporaryUploadPurpose $purpose): TemporaryUpload
    {
        $disk = PublicMediaDisk::name();
        $path = $file->store("temporary-uploads/{$user->id}/{$purpose->value}", ['disk' => $disk]);

        try {
            return TemporaryUpload::query()->create([
                'user_id' => $user->id,
                'purpose' => $purpose,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => (string) $file->getMimeType(),
                'size' => $file->getSize(),
                'expires_at' => now()->addDay(),
            ]);
        } catch (Throwable $throwable) {
            if (is_string($path)) {
                Storage::disk($disk)->delete($path);
            }

            throw $throwable;
        }
    }
}
