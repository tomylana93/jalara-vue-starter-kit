<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Uploads\DeleteTemporaryUpload;
use App\Actions\Uploads\StageTemporaryUpload;
use App\Enums\TemporaryUploadPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreBrandingUploadRequest;
use App\Settings\StyleSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class BrandingUploadController extends Controller
{
    public function store(
        StoreBrandingUploadRequest $request,
        StageTemporaryUpload $stageTemporaryUpload,
    ): JsonResponse {
        $upload = $stageTemporaryUpload->handle(
            $request->user(),
            $request->file('file'),
            TemporaryUploadPurpose::Branding,
            $request->string('field')->toString(),
        );

        return response()->json([
            'id' => $upload->id,
            'name' => $upload->original_name,
            'size' => $upload->size,
            'type' => $upload->mime_type,
        ], 201);
    }

    public function destroy(
        Request $request,
        string $temporaryUpload,
        DeleteTemporaryUpload $deleteTemporaryUpload,
    ): Response {
        Gate::authorize('update', StyleSettings::class);

        abort_unless($deleteTemporaryUpload->handle(
            $request->user(),
            $temporaryUpload,
            TemporaryUploadPurpose::Branding,
        ), 404);

        return response()->noContent();
    }
}
