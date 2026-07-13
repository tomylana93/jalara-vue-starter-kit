<?php

namespace App\Console\Commands;

use App\Models\TemporaryAvatarUpload;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('media:purge-expired-avatar-uploads')]
#[Description('Remove expired temporary avatar uploads.')]
class PurgeExpiredAvatarUploads extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        TemporaryAvatarUpload::query()
            ->where('expires_at', '<=', now())
            ->cursor()
            ->each(fn (TemporaryAvatarUpload $upload) => $upload->delete());

        return self::SUCCESS;
    }
}
