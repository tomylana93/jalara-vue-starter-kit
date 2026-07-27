# Action Pattern Refactor Design

## Status

Approved for implementation planning on 2026-07-16.

## Problem

The application already uses single-purpose Action classes for operations such
as updating a user profile, updating a password, promoting a staged avatar, and
updating general settings. Six other application operations still execute
business logic directly inside HTTP controllers or Artisan commands. This
mixes transport and presentation concerns with application behavior, makes the
operations harder to test independently, and leaves inconsistent boundaries
for similar workflows.

## Goals

- Extract all six audited operations into single-purpose Action classes.
- Keep controllers and Artisan commands focused on input, presentation, and
  transport concerns.
- Preserve every existing HTTP response, command signature, exit code, console
  message, database effect, and filesystem effect.
- Use constructor injection for Action dependencies.
- Add direct tests for each Action while retaining existing integration tests.

## Non-goals

- No route, request validation, policy, frontend, schema, or dependency changes.
- No general reorganization of existing Actions outside the six audited
  operations.
- No changes to the public behavior of avatar, authorization, or Super Admin
  workflows.
- No extraction of query-only controller methods or model lifecycle invariants.

## Architecture

Each business operation becomes a final Action class with a public `handle()`
method, matching the existing project convention. Controllers and commands
remain adapters that read HTTP or CLI input, invoke an Action, and translate its
result into JSON, redirects, toast messages, console output, or exit codes.

Actions must not depend on an HTTP request, an Artisan command, Inertia, or
console output components. External collaborators are supplied through
constructor injection. Result types are placed beside the Action domain that
uses them rather than in a generic shared directory.

The new boundaries are:

- `App\Actions\Authorization\SyncAuthorization`
- `App\Actions\Authorization\InitializeSuperAdmin`
- `App\Actions\Profile\StageTemporaryAvatarUpload`
- `App\Actions\Profile\DeleteTemporaryAvatarUpload`
- `App\Actions\Profile\RemoveUserAvatar`
- `App\Actions\Profile\PurgeExpiredAvatarUploads`

## Action Contracts

### Synchronize authorization

`SyncAuthorization::handle(bool $dryRun = false): AuthorizationSyncResult`

`AuthorizationCatalog` and `PermissionRegistrar` are constructor dependencies.
The Action calculates role and permission creation/deletion and permission
attachment/detachment for both modes. In apply mode it performs those changes
and clears the permission cache after successful synchronization. In dry-run
mode it performs no mutation, including no cache clearing.

`AuthorizationSyncResult` contains:

- role names to create and delete;
- permission names to create and delete;
- permission names to attach and detach, keyed by role name;
- whether the result represents a dry-run.

The command owns option parsing and renders this result using its existing
messages and exit codes.

### Initialize the Super Admin

`InitializeSuperAdmin::handle(array $attributes, bool $resetPassword): User`

The command reads and validates the existing configuration before invocation.
The attributes have the following required shape:

```php
array{
    name: string,
    email: string,
    phone: ?string,
    status: UserStatus,
    email_verified: bool,
    password: ?string,
}
```

The Action finds the protected system user or matching email, restores a
soft-deleted user when necessary, assigns the configured attributes, preserves
the existing password-reset semantics, saves quietly, ensures the Super Admin
role exists, and enforces that role. It returns the initialized user so the
command can render the existing success message.

### Stage a temporary avatar

`StageTemporaryAvatarUpload::handle(User $user, UploadedFile $file): TemporaryAvatarUpload`

The Action selects the configured avatar disk, stores the file under the user's
temporary-avatar directory, creates a `TemporaryAvatarUpload` expiring in one
day, and returns it. If record creation fails after the file is stored, the
Action removes the stored file before rethrowing the original failure. The
controller maps the returned model to the existing JSON response shape.

### Delete a temporary avatar

`DeleteTemporaryAvatarUpload::handle(User $user, string $uploadId): TemporaryAvatarDeletionResult`

`TemporaryAvatarDeletionResult` is an enum with `Deleted`, `Missing`, and
`Forbidden` cases. The distinction preserves the current behavior: an unknown
upload returns HTTP 204, an upload owned by another user returns HTTP 404, and a
successful deletion returns HTTP 204. The Action owns lookup, ownership
comparison, and deletion; the controller owns HTTP translation.

### Remove a user's avatar

`RemoveUserAvatar::handle(User $user): void`

The Action clears the user's `avatar` media collection. The controller retains
the existing toast and redirect.

### Purge expired temporary avatars

`PurgeExpiredAvatarUploads::handle(): int`

The Action streams expired uploads with a cursor, deletes them through the
model lifecycle, and returns the number deleted. The command invokes the Action
and retains its existing success exit code.

## Data and Control Flow

```text
HTTP request / CLI invocation
        |
        v
Controller / Command
- validate or parse transport input
- invoke one Action
        |
        v
Action
- execute one application use-case
- return model, result, enum, count, or void
        |
        v
Controller / Command
- render the unchanged HTTP or console response
```

No Action calls another new Action in this refactor. This keeps each operation
independently testable and avoids creating an implicit orchestration hierarchy.

## Error Handling

- Database and filesystem exceptions that cannot be recovered are allowed to
  propagate so Laravel can report them normally.
- `StageTemporaryAvatarUpload` provides compensating cleanup when persistence
  fails after storing a file.
- `DeleteTemporaryAvatarUpload` reports ownership and existence outcomes using
  its result enum instead of throwing HTTP-specific exceptions.
- `SyncAuthorization` does not mutate records or caches during dry-run.
- Authorization cache clearing happens only after a successful applied sync.
- `InitializeSuperAdmin` preserves `saveQuietly()`, soft-delete restoration,
  password behavior, email-verification behavior, the system flag, and role
  enforcement.

## Testing Strategy

Implementation follows red-green-refactor for each Action.

- Add Action tests for synchronization calculation, apply mode, dry-run mode,
  permission attachment/detachment, deletion, and cache behavior.
- Add Action tests for creating, restoring, and resetting the Super Admin,
  including verified and unverified email states.
- Add Action tests for staging an avatar, metadata and expiry, storage disk and
  path, and cleanup after persistence failure.
- Add Action tests for each temporary-avatar deletion result.
- Add an Action test for clearing a user's avatar collection.
- Add Action tests for purging only expired uploads and returning the deleted
  count.
- Retain controller and command feature tests as contract-level regression
  coverage for responses, output, exit codes, and side effects.

Targeted tests run after each Action. The full project finishing gate runs after
all six integrations. Wayfinder generation is not required because routes,
controller signatures, invokable actions, route names, and route parameters do
not change.

## Acceptance Criteria

1. All six audited operations are implemented by the specified Action classes.
2. Their controllers and commands contain only transport, presentation, option
   parsing, configuration validation, Action invocation, and result mapping.
3. `SyncAuthorization` no longer uses `app()` or `resolve()` and performs no
   mutation during dry-run.
4. Failed temporary-avatar persistence does not leave an orphaned file.
5. Temporary-avatar deletion preserves the current missing-versus-forbidden
   HTTP behavior.
6. All existing HTTP responses, console messages, command signatures, exit
   codes, and visible side effects remain unchanged.
7. New direct Action tests and all existing affected feature tests pass.
8. The repository finishing gate passes with no unrelated changes.

## Rejected Alternatives

### Group operations into domain services

A shared avatar service or authorization service would reduce the number of
classes, but each service would expose multiple unrelated commands and diverge
from the project's established single-purpose Action convention.

### Extract only the three complex operations

This would produce a smaller diff but leave the same operation type split
between Actions and entrypoints. It also would not satisfy the requirement to
address all six audit findings.

### Move every mutation into an Action

The login listener and the `TemporaryAvatarUpload` deleting hook remain in
place. The listener is already a single-purpose event handler, while deleting
the stored file is a model lifecycle invariant. Wrapping either in another
Action would add indirection without creating a clearer application boundary.
