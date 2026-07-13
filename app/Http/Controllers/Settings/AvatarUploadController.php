<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTemporaryAvatarUploadRequest;
use App\Models\TemporaryAvatarUpload;
use App\Support\MediaDisk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AvatarUploadController extends Controller
{
    /**
     * Stage a temporary avatar upload.
     */
    public function store(StoreTemporaryAvatarUploadRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $user = $request->user();
        $disk = MediaDisk::avatar();

        $path = $file->store("temporary-avatars/{$user->id}", [
            'disk' => $disk,
        ]);

        $upload = TemporaryAvatarUpload::create([
            'user_id' => $user->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'expires_at' => now()->addDay(),
        ]);

        return response()->json([
            'id' => $upload->id,
            'name' => $upload->original_name,
            'size' => $upload->size,
            'type' => $upload->mime_type,
        ], 201);
    }

    /**
     * Destroy a temporary avatar upload.
     */
    public function destroy(Request $request, TemporaryAvatarUpload $temporaryAvatarUpload): Response
    {
        abort_unless($temporaryAvatarUpload->user_id === $request->user()->id, 404);

        if (Storage::disk($temporaryAvatarUpload->disk)->exists($temporaryAvatarUpload->path)) {
            Storage::disk($temporaryAvatarUpload->disk)->delete($temporaryAvatarUpload->path);
        }

        $temporaryAvatarUpload->delete();

        return response()->noContent();
    }
}
