# User Domain Migration Design

## Goal

Complete the clean-slate user-domain migration so UUID users, authentication-related attributes, soft deletion, and dependent tables work consistently in new installations.

## Scope

This work covers:

- The initial `users` schema, including UUID identity, nullable phone, status, password-change flag, login timestamp, and soft deletes.
- UUID-compatible references from `sessions` and `passkeys`.
- `UserStatus` enum integration and `HasOptions` coverage.
- `User` model configuration, PHPDoc, casts, fillable attributes, relationships, and soft-delete behavior.
- `UserFactory` defaults and states for the new attributes.
- Existing Fortify authentication, password reset, verification, two-factor, and passkey flows.
- Focused Pest coverage for schema, model behavior, factory defaults, relationships, and authentication regressions.

This is a clean-slate starter-kit change. It does not provide a backfill or upgrade path for an already-deployed database. It does not add login restrictions for disabled or suspended users; status is stored and exposed as domain data until an explicit authentication policy is requested.

## Current Gaps

- The migration references `UserStatus::Active` without importing `App\\Enums\\UserStatus`.
- `users.id` is UUID, but `sessions.user_id` and `passkeys.user_id` currently use integer-oriented schema helpers.
- `User` still documents `id` as an integer and does not configure non-incrementing string keys.
- `User` does not use `SoftDeletes`, does not cast `status`, `must_change_password`, or `last_login_at`, and does not expose the new writable attributes.
- `UserFactory` does not define valid defaults for the new user columns.
- Existing authentication and passkey tests do not prove that UUID users work through all configured flows.

## Architecture

`users.id` is the canonical UUID identity. Eloquent is configured with a string, non-incrementing primary key, while dependent tables store UUID values in `user_id` columns. `passkeys.user_id` retains referential integrity to `users.id`; the sessions table receives a UUID-compatible indexed key consistent with Laravel’s session-table convention.

`UserStatus` remains a backed enum with `Active`, `Disable`, and `Suspend` values. The model casts the database string to the enum, and the migration uses the enum value for the default. No status-based authentication policy is introduced in this migration.

The model remains the boundary for persistence concerns: casts, hidden/fillable attributes, soft deletes, and the passkey relationship. The factory supplies stable, realistic defaults so existing tests and new tests can create users without manually knowing schema details.

## Data Flow

1. A new database creates the users table with UUID primary keys and all user-domain columns.
2. Sessions and passkeys store the same UUID values as `users.id`.
3. Eloquent hydrates UUID IDs as strings and maps status to `UserStatus`.
4. Fortify retrieves users through the existing Eloquent provider without route or payload changes.
5. Soft-deleted users are excluded by default through `SoftDeletes`; no authentication behavior is changed beyond standard Eloquent scope behavior.

## Error Handling and Compatibility

- Migration ordering must ensure `users` exists before dependent foreign keys are created.
- The clean-slate migration must be reversible in dependency order.
- No broad exception swallowing or custom authentication failure is added.
- Existing route names, middleware, frontend payloads, Fortify features, and password behavior remain unchanged.
- Existing passkey and session functionality must continue to resolve the authenticated user by UUID.

## Testing Strategy

- Add focused Pest tests for UUID persistence, model casts, fillable/hidden behavior, soft deletes, status options, and factory defaults.
- Add a schema-oriented feature test that verifies UUID column types, dependent UUID user keys, foreign-key behavior, and user columns.
- Extend authentication tests to assert login, invalid credentials, password reset, email verification, two-factor, and passkey-related flows still work with factory-created UUID users.
- Run the focused test files first, then the full test suite, Pint, and the project’s existing static checks.

## Completion Criteria

- Fresh migrations complete successfully and roll back successfully.
- Users persist with UUID string IDs and dependent tables accept those IDs.
- `User` correctly casts status, boolean, and timestamp fields and supports soft deletes.
- Factory-created users satisfy all new column constraints and existing tests remain green.
- Existing Fortify and passkey behavior remains compatible.
- No status-based login restriction is introduced.
- No dependency changes are made.
- Spec and plan are present together and remain uncommitted, as requested.
