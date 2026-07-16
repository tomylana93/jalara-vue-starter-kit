<?php

namespace App\Console\Commands;

use App\Actions\Authorization\AuthorizationSyncResult;
use App\Actions\Authorization\SyncAuthorization as SyncAuthorizationAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('auth:sync-authorization {--dry-run}')]
#[Description('Synchronize the roles and permissions tables with the authorization catalog.')]
class SyncAuthorization extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SyncAuthorizationAction $syncAuthorization): int
    {
        $result = $syncAuthorization->handle((bool) $this->option('dry-run'));

        if ($result->dryRun) {
            $this->renderDryRun($result);
        } else {
            $this->components->info('Authorization catalog synchronized.');
        }

        return self::SUCCESS;
    }

    private function renderDryRun(AuthorizationSyncResult $result): void
    {
        $this->components->info('Dry run: no records will be modified.');
        $this->components->twoColumnDetail('Roles to create', $result->rolesToCreate === [] ? 'none' : implode(', ', $result->rolesToCreate));
        $this->components->twoColumnDetail('Permissions to create', $result->permissionsToCreate === [] ? 'none' : implode(', ', $result->permissionsToCreate));
        $this->components->twoColumnDetail('Roles to delete', $result->rolesToDelete === [] ? 'none' : implode(', ', $result->rolesToDelete));
        $this->components->twoColumnDetail('Permissions to delete', $result->permissionsToDelete === [] ? 'none' : implode(', ', $result->permissionsToDelete));

        foreach ($result->permissionsToAttachByRole as $roleName => $permissionsToAttach) {
            $this->components->twoColumnDetail(
                "Permissions to attach to [{$roleName}]",
                $permissionsToAttach === [] ? 'none' : implode(', ', $permissionsToAttach)
            );

            $permissionsToDetach = $result->permissionsToDetachByRole[$roleName] ?? [];

            $this->components->twoColumnDetail(
                "Permissions to detach from [{$roleName}]",
                $permissionsToDetach === [] ? 'none' : implode(', ', $permissionsToDetach)
            );
        }
    }
}
