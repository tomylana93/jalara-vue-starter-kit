<?php

namespace App\Actions\Authorization;

final class AuthorizationSyncResult
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
        public readonly array $rolesToCreate,
        public readonly array $permissionsToCreate,
        public readonly array $rolesToDelete,
        public readonly array $permissionsToDelete,
        public readonly array $permissionsToAttachByRole,
        public readonly array $permissionsToDetachByRole,
        public readonly bool $dryRun,
    ) {}
}
