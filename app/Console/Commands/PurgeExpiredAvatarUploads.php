<?php

namespace App\Console\Commands;

use App\Actions\Profile\PurgeExpiredAvatarUploads as PurgeExpiredAvatarUploadsAction;
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
    public function handle(PurgeExpiredAvatarUploadsAction $purgeExpiredAvatarUploads): int
    {
        $purgeExpiredAvatarUploads->handle();

        return self::SUCCESS;
    }
}
