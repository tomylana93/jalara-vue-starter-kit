<?php

namespace App\Http\Controllers\Profile;

use App\Actions\Profile\StageTemporaryAvatarUpload;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreTemporaryAvatarUploadRequest;
use App\Models\TemporaryAvatarUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AvatarUploadController extends Controller
{
    /**
     * Stage a temporary avatar upload.
     */
    public function store(StoreTemporaryAvatarUploadRequest $request, StageTemporaryAvatarUpload $stageTemporaryAvatarUpload): JsonResponse
    {
        $upload = $stageTemporaryAvatarUpload->handle($request->user(), $request->file('file'));

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
