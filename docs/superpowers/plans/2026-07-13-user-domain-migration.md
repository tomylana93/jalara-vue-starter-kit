# User Domain Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete the clean-slate UUID user-domain migration and align dependent schema, Eloquent, factories, authentication, and tests.

**Architecture:** `users.id` is a UUID string primary key. `User` owns persistence behavior through casts, `SoftDeletes`, and the existing passkey relationship; sessions and passkeys store UUID-compatible `user_id` values. Fortify contracts and route behavior remain unchanged.

**Tech Stack:** PHP 8.5, Laravel 13, Eloquent, Fortify v1, Laravel Passkeys, Pest 4, SQLite/test database, Laravel Pint.

## Global Constraints

- This is a clean-slate starter-kit schema update; no existing-data backfill is required.
- Do not add status-based login blocking; `UserStatus` is persisted and cast only.
- Do not change dependencies, route names, frontend payloads, Fortify contracts, or passkey APIs.
- Use UUID string IDs consistently in `users`, `sessions`, and `passkeys`.
- Use Pest tests and existing factories; do not delete tests.
- After PHP changes, run `vendor/bin/pint --dirty --format agent`.
- Do not commit this plan, the spec, or implementation changes during this task.

---

### Task 1: Correct the clean-slate database schema

**Files:**
- Modify: `database/migrations/0001_01_01_000000_create_users_table.php`
- Modify: `database/migrations/2024_01_01_000000_create_passkeys_table.php`
- Test: `tests/Feature/UserSchemaTest.php`

**Interfaces:**
- Produces: fresh migrations where `users.id`, `sessions.user_id`, and `passkeys.user_id` use UUID-compatible columns.

- [ ] **Step 1: Write schema regression tests**

Create `tests/Feature/UserSchemaTest.php` with Pest tests that inspect the schema after migrations:

```php
use Illuminate\Support\Facades\Schema;

test('user identity and dependent keys use uuid-compatible columns', function () {
    expect(Schema::getColumnType('users', 'id'))->toBe('uuid')
        ->and(Schema::getColumnType('sessions', 'user_id'))->toBe('uuid')
        ->and(Schema::getColumnType('passkeys', 'user_id'))->toBe('uuid');
});

test('users contain the new domain columns', function () {
    expect(Schema::hasColumns('users', [
        'phone', 'status', 'must_change_password', 'last_login_at', 'deleted_at',
    ]))->toBeTrue();
});
```

- [ ] **Step 2: Run the schema tests and confirm the current failure**

Run:

```bash
php artisan test --compact tests/Feature/UserSchemaTest.php
```

Expected: FAIL or migration error because the migration has a missing enum import and integer-oriented dependent keys.

- [ ] **Step 3: Fix the users migration**

Import `App\Enums\UserStatus` and keep the UUID primary key. Use `UserStatus::Active->value` for the string default, preserving the database column type. Keep the new columns nullable/defaulted as currently intended.

- [ ] **Step 4: Fix dependent user keys**

Change `sessions.user_id` to a nullable UUID column with an index. Change `passkeys.user_id` to a UUID foreign key constrained to `users.id` with cascade delete. Keep passkeys’ own integer primary key unchanged.

- [ ] **Step 5: Re-run schema tests and migrations**

Run:

```bash
php artisan migrate:fresh --env=testing --no-interaction
php artisan test --compact tests/Feature/UserSchemaTest.php
```

Expected: migrations complete and the schema tests pass.

### Task 2: Align the User model and enum option behavior

**Files:**
- Modify: `app/Models/User.php`
- Modify: `app/Enums/UserStatus.php`
- Modify: `app/Concerns/HasOptions.php`
- Test: `tests/Unit/Models/UserTest.php`
- Test: `tests/Unit/Enums/UserStatusTest.php`

**Interfaces:**
- Produces: `User` instances with string UUID keys, `SoftDeletes`, enum/boolean/datetime casts, and the existing passkey relationship.

- [ ] **Step 1: Add failing model tests**

Cover these exact assertions:

```php
test('users use uuid string keys and soft deletes', function () {
    $user = User::factory()->create();

    expect($user->getKey())->toBeString()
        ->and($user->getIncrementing())->toBeFalse()
        ->and($user->getKeyType())->toBe('string');

    $user->delete();

    expect(User::withTrashed()->find($user->getKey()))->not->toBeNull()
        ->and(User::find($user->getKey()))->toBeNull();
});

test('users cast new attributes', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Suspend,
        'must_change_password' => true,
        'last_login_at' => now(),
    ]);

    expect($user->status)->toBe(UserStatus::Suspend)
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->last_login_at)->toBeInstanceOf(Carbon::class);
});
```

Add enum tests for `options()` and its additional-field validation, matching the existing `HasOptions` contract.

- [ ] **Step 2: Implement model alignment**

Add `SoftDeletes`, update PHPDoc (`id` as `string`, plus new properties), add `phone`, `status`, and `must_change_password` to the fillable attribute list, and configure:

```php
'status' => UserStatus::class,
'must_change_password' => 'boolean',
'last_login_at' => 'datetime',
'deleted_at' => 'datetime',
```

Set `$keyType = 'string'` and `$incrementing = false` using the project’s established model style. Keep password and security fields hidden.

- [ ] **Step 3: Verify enum and model tests**

Run:

```bash
php artisan test --compact tests/Unit/Models/UserTest.php tests/Unit/Enums/UserStatusTest.php
```

Expected: PASS.

### Task 3: Make the factory produce valid users

**Files:**
- Modify: `database/factories/UserFactory.php`
- Test: `tests/Unit/Factories/UserFactoryTest.php`

**Interfaces:**
- Produces: factory users with valid UUID, active status, nullable phone, `must_change_password = false`, and nullable login timestamp.

- [ ] **Step 1: Add factory tests**

Assert that a default factory user has `UserStatus::Active`, `false` for `must_change_password`, a null `last_login_at`, a null-or-valid unique phone, and a string UUID key. Add a state for a user who must change password if the application’s factory conventions support named states.

- [ ] **Step 2: Update factory defaults**

Import `UserStatus` and add explicit values for `phone`, `status`, `must_change_password`, `last_login_at`, and any new security columns required by the schema. Use `fake()->unique()` only for generated phone values if a phone default is enabled; otherwise keep the default null to avoid unnecessary test coupling.

- [ ] **Step 3: Run factory and existing auth tests**

Run:

```bash
php artisan test --compact tests/Unit/Factories/UserFactoryTest.php tests/Feature/Auth tests/Feature/Settings
```

Expected: PASS, with existing authentication and settings behavior unchanged.

### Task 4: Verify UUID compatibility across authentication and passkeys

**Files:**
- Modify: `tests/Feature/Auth/AuthenticationTest.php`
- Modify: `tests/Feature/Auth/PasswordResetTest.php`
- Modify: `tests/Feature/Auth/EmailVerificationTest.php`
- Modify: `tests/Feature/Auth/TwoFactorChallengeTest.php`
- Modify: `tests/Feature/Settings/SecurityTest.php`
- Test: `tests/Feature/Auth/UuidUserAuthenticationTest.php`

**Interfaces:**
- Produces: regression coverage proving existing Fortify/passkey flows accept UUID-backed users without new route contracts.

- [ ] **Step 1: Add UUID authentication regression coverage**

Create one focused feature test that creates a user through `UserFactory`, logs in with valid credentials, asserts authentication, logs out, and verifies invalid credentials still fail. Add password-reset and email-verification assertions only where existing test helpers do not already exercise the same path with factory users.

- [ ] **Step 2: Verify special auth flows**

Run the existing two-factor, password confirmation, password reset, email verification, passkey, and settings tests. Confirm no code changes are needed merely because the model key is now a UUID.

- [ ] **Step 3: Add only required compatibility fixes**

If a package relationship or test assumes an integer key, update the narrowest model/migration/test boundary. Do not change Fortify username configuration, routes, response payloads, or status behavior.

### Task 5: Run final verification and review the diff

**Files:**
- Verify: all modified PHP files and new tests.

- [ ] **Step 1: Run focused tests**

```bash
php artisan test --compact tests/Feature/UserSchemaTest.php tests/Unit/Models/UserTest.php tests/Unit/Enums/UserStatusTest.php tests/Unit/Factories/UserFactoryTest.php tests/Feature/Auth/UuidUserAuthenticationTest.php
```

- [ ] **Step 2: Run the full test suite**

```bash
php artisan test --compact
```

- [ ] **Step 3: Format changed PHP files**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 4: Run existing project quality checks**

Use the repository’s documented static-analysis and frontend/type checks from `composer.json`, `package.json`, and `docs/development-workflow.md`; do not add new tooling.

- [ ] **Step 5: Review scope and status**

Run `git diff --check` and inspect `git diff`. Confirm no dependency files, route contracts, frontend payloads, or status-based login policy changed. Leave all work—including these spec and plan documents—uncommitted.
