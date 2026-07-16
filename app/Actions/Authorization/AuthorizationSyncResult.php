<?php

namespace App\Actions\Authorization;

final readonly class AuthorizationSyncResult
{
    /**
     * @param  list<string>  $rolesToCreate
     * @param  list<string>  $permissionsToCreate
     * @param  list<string>  $rolesToDelete
     * @param  list<string>  $permissionsToDelete
     * @param  array<string, list<string>>  $permissionsToAttachByRole
     * @param  array<string, list<string>>  $permissionsToDetachByRole
     */
    public function __construct(
        public array $rolesToCreate,
        public array $permissionsToCreate,
        public array $rolesToDelete,
        public array $permissionsToDelete,
        public array $permissionsToAttachByRole,
        public array $permissionsToDetachByRole,
        public bool $dryRun,
    ) {}
}
