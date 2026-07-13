# Spatie Authorization Design

## Problem Statement

The application has Spatie Laravel Permission installed and its published configuration and migration are present, but the package is not yet integrated with the UUID-based `User` model or migrated. The application needs a version-controlled, strict authorization catalog, a repeatable Super Admin initializer, and protections for the system account.

## Solution

Define roles and permissions in PHP-backed enums and a single authorization catalog. Provide one command to strictly synchronize database roles, permissions, and role assignments from that catalog, and another to create or repair the configured system Super Admin account. The system account is a durable protected user with the `super-admin` role; Laravel Gate grants it every ability without duplicating permissions.

## User Stories

1. As an operator, I want a single command to synchronize roles and permissions from application code, so that no seeder is required.
2. As an operator, I want synchronization to strictly remove undeclared roles and permissions, so that the database cannot drift from the catalog.
3. As a developer, I want role and permission names represented by backed enums, so that authorization names are reusable and type-safe.
4. As an operator, I want a dry-run option, so that I can inspect strict synchronization changes before applying them.
5. As an operator, I want an initialization command for the Super Admin, so that the required system account can be created, restored, and reconciled consistently.
6. As an operator, I want Super Admin profile fields to come from configuration, so that environment-specific values are not hard-coded in the command.
7. As an operator, I want the initial password to default to `password` only in local and testing environments, so that local setup is convenient without creating a production default credential.
8. As an operator, I want production initialization to fail without an explicitly configured Super Admin password, so that a predictable production credential cannot be created.
9. As an operator, I want repeated initialization to preserve the current password unless I explicitly request a reset, so that normal reconciliation does not unexpectedly invalidate access.
10. As an operator, I want the Super Admin account restored if soft-deleted, so that the system account remains recoverable.
11. As a system owner, I want the Super Admin account protected from deletion and role mutation, so that every entry point preserves the system administrator.
12. As a Super Admin, I want every normal Laravel authorization check to succeed, so that the system role bypasses granular permissions.
13. As a future feature developer, I want non-Super-Admin authorization checks to use permissions rather than role names, so that authorization remains granular.

## Implementation Decisions

- Use `App\Enums\Role` and `App\Enums\Permission` backed enums. Initial contents are `Role::SuperAdmin` and no permission cases.
- Use one focused catalog class as the source of truth for declared roles, permissions, and role-to-permission mappings. It must expose scalar values suitable for Spatie's APIs.
- Provide `auth:sync-authorization`. It creates declared permissions and roles, synchronizes each role's permissions, then removes all undeclared permissions and roles. `--dry-run` reports intended creates, synchronization changes, and deletions without writing data.
- The command is intentionally strict. Removing a catalog entry removes its related pivot records through the permission tables' foreign-key cascades and removes the role or permission record.
- Provide `auth:init-superadmin {--reset-password}`. It finds the durable system account, otherwise finds the configured email including soft-deleted records, otherwise creates it. It reconciles configured non-secret profile values and enforces exactly `Role::SuperAdmin`.
- Add a boolean `is_system` column to the users table. It identifies the protected account independently of an email address, allowing configuration-driven email changes.
- Add `HasRoles` to `User`. The published Spatie pivot migration must use a UUID-compatible morph key because `users.id` is a UUID. This migration is currently uncommitted and must be corrected before the first migration run; a deployed migration must instead be followed by a new compatible migration.
- Protect system users at the domain-model layer: deletion must be rejected and every public Spatie role mutation path must reject modifications that would remove `super-admin` or add another role. The initializer retains a narrowly scoped internal path to enforce the required role.
- Add a Gate `before` callback that returns `true` for the protected Super Admin with the `super-admin` role and `null` for everybody else. Application authorization must use Laravel's `can`/Gate APIs so the bypass applies.
- Add `config/superadmin.php` for name, email, phone, status, verification state, and password. It uses environment variables only in the config file. In local and testing, the password fallback is `password`; production requires `SUPER_ADMIN_PASSWORD`.
- Existing package-install changes (`composer.json`, `composer.lock`, `config/permission.php`, and the published permission migration) are user-owned and must not be discarded or overwritten wholesale.

## Testing Decisions

- Use Pest feature tests with the repository's existing `RefreshDatabase` setup for command, persistence, and Gate behavior.
- Test externally observable behavior: strict creation and pruning, dry-run non-mutation, command idempotency, profile reconciliation, password preservation/reset, local/testing password fallback, production missing-password failure, account restoration, and authorization bypass.
- Test the protected account's deletion and role-mutation behavior through the public model APIs, not by asserting private implementation details.
- Update or add unit tests for the enum/catalog contract only where a focused unit seam is useful. Follow existing `tests/Feature` and `tests/Unit` Pest style.

## Out of Scope

- A user or role management UI.
- Assigning direct permissions to users.
- Teams, multiple guards, wildcard permissions, API authorization, or role administration workflows.
- Automatically scheduling either command.
- Protection against direct database writes outside Laravel; database operators retain their normal administrative authority.

## Further Notes

- The Super Admin role has no stored permissions initially. Its universal access is implemented by `Gate::before`.
- `auth:sync-authorization` is a deliberate deployment operation; `--dry-run` should be used before a catalog deletion when operational review is needed.
