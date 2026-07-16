<?php

namespace App\Console\Commands;

use App\Actions\Uploads\PruneTemporaryUploads as PruneTemporaryUploadsAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('uploads:prune-temporary {--hours=24 : Retention period in hours}')]
#[Description('Remove temporary uploads older than the retention period.')]
class PruneTemporaryUploads extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PruneTemporaryUploadsAction $pruneTemporaryUploads): int
    {
        $pruneTemporaryUploads->handle((int) $this->option('hours'));

        return self::SUCCESS;
    }
}
