<?php

namespace App\Actions\Profile;

use App\Actions\Uploads\StageTemporaryUpload;
use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;

final readonly class StageTemporaryAvatarUpload
{
    public function __construct(private StageTemporaryUpload $stageTemporaryUpload) {}

    /**
     * Store a temporary avatar upload and record its metadata.
     */
    public function handle(User $user, UploadedFile $file): TemporaryUpload
    {
        return $this->stageTemporaryUpload->handle($user, $file, TemporaryUploadPurpose::Avatar);
    }
}
