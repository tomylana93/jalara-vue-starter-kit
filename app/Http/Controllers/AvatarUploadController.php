<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemporaryAvatarUploadRequest;
use App\Models\TemporaryAvatarUpload;
use App\Support\MediaDisk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

        $upload = TemporaryAvatarUpload::query()->create([
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

    public function destroy(Request $request, string $temporaryAvatarUpload): Response
    {
        $upload = TemporaryAvatarUpload::query()->find($temporaryAvatarUpload);

        if ($upload === null) {
            return response()->noContent();
        }

        abort_unless($upload->user_id === $request->user()->id, 404);

        $upload->delete();

        return response()->noContent();
    }
}
