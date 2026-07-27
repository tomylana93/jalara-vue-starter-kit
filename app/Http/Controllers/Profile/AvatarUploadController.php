<?php

namespace App\Http\Controllers\Profile;

use App\Actions\Profile\DeleteTemporaryAvatarUpload;
use App\Actions\Profile\TemporaryAvatarDeletionResult;
use App\Actions\Uploads\StageTemporaryUpload;
use App\Enums\TemporaryUploadPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreTemporaryAvatarUploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AvatarUploadController extends Controller
{
    /**
     * Stage a temporary avatar upload.
     */
    public function store(StoreTemporaryAvatarUploadRequest $request, StageTemporaryUpload $stageTemporaryUpload): JsonResponse
    {
        $upload = $stageTemporaryUpload->handle(
            $request->user(),
            $request->file('file'),
            TemporaryUploadPurpose::Avatar,
        );

        return response()->json([
            'id' => $upload->id,
            'name' => $upload->original_name,
            'size' => $upload->size,
            'type' => $upload->mime_type,
        ], 201);
    }

    public function destroy(Request $request, string $temporaryAvatarUpload, DeleteTemporaryAvatarUpload $deleteTemporaryAvatarUpload): Response
    {
        $result = $deleteTemporaryAvatarUpload->handle($request->user(), $temporaryAvatarUpload);

        abort_if($result === TemporaryAvatarDeletionResult::Forbidden, 404);

        return response()->noContent();
    }
}
