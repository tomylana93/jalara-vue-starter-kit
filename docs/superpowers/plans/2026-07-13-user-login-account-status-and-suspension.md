# User Login Account Status and Suspension Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prevent deleted and non-active users from logging in and automatically suspend active users after a configurable number of incorrect passwords.

**Architecture:** Persist `failed_login_attempts` and `suspended_until` on `users`. Route Fortify authentication through a focused application action that performs soft-delete/status checks, expiry handling, password verification, and atomic failure tracking before Fortify prepares the authenticated session. Keep Fortify's existing username/IP rate limiter as a separate defense.

**Tech Stack:** PHP 8.5, Laravel 13, Laravel Fortify 1, Eloquent, SQLite test database, Pest 4.

## Global Constraints

- Use `config/auth.php` with `AUTH_LOGIN_MAX_FAILED_ATTEMPTS=5` and `AUTH_LOGIN_SUSPENSION_MINUTES=15` defaults.
- Deleted, disabled, and manually suspended users must not authenticate.
- Automatic suspension is represented by `status=suspend` plus a non-null `suspended_until`; `suspended_until=null` remains a manual suspension.
- Preserve generic login failure behavior and Fortify's existing username/IP rate limiter.
- Use existing Laravel/Pest conventions, factories, and feature tests.
- Run `vendor/bin/pint --dirty --format agent` after PHP changes.

---

### Task 1: Add persistent login-security fields

**Files:**
- Modify: `database/migrations/0001_01_01_000000_create_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Test: `tests/Feature/UserSchemaTest.php`
- Test: `tests/Unit/Models/UserTest.php`

**Interfaces:**
- Produces `User::$failed_login_attempts` as an integer and `User::$suspended_until` as a nullable datetime.

- [ ] **Step 1: Write the failing schema and cast tests**

Add assertions that `users` contains `failed_login_attempts` and `suspended_until`, and that a created user casts the counter to `int` and the expiry to a Carbon date.

- [ ] **Step 2: Run the focused tests to verify failure**

Run:

```bash
php artisan test --compact tests/Feature/UserSchemaTest.php tests/Unit/Models/UserTest.php
```

Expected: FAIL because the columns and model casts do not exist.

- [ ] **Step 3: Add the migration and model/factory defaults**

Create the migration with `failed_login_attempts` defaulting to `0` and nullable `suspended_until`. Add both attributes to the User PHPDoc/casts and add factory defaults of `0` and `null`.

- [ ] **Step 4: Run the focused tests to verify success**

Run the same command and expect PASS.

- [ ] **Step 5: Format PHP files**

Run:

```bash
vendor/bin/pint --dirty --format agent
```

### Task 2: Add configurable login-security policy

**Files:**
- Modify: `config/auth.php`
- Test: `tests/Feature/Auth/AuthenticationTest.php`

**Interfaces:**
- Produces `config('auth.login_security.max_failed_attempts')` with default `5`.
- Produces `config('auth.login_security.suspension_minutes')` with default `15`.

- [ ] **Step 1: Write configuration assertions**

Add a test asserting the default values and use `Config::set()` in authentication tests to verify the implementation can use non-default values without changing environment files.

- [ ] **Step 2: Run the focused test to verify failure**

Run:

```bash
php artisan test --compact tests/Feature/Auth/AuthenticationTest.php
```

Expected: FAIL for the new configuration assertions.

- [ ] **Step 3: Add the `auth.login_security` configuration block**

Use `env('AUTH_LOGIN_MAX_FAILED_ATTEMPTS', 5)` and `env('AUTH_LOGIN_SUSPENSION_MINUTES', 15)`; do not add dependencies or modify committed environment files.

- [ ] **Step 4: Run the focused test to verify success**

Run the same command and expect PASS for the configuration assertions and existing tests.

### Task 3: Implement the Fortify account-status authentication action

**Files:**
- Create: `app/Actions/Fortify/AuthenticateUser.php`
- Modify: `app/Providers/FortifyServiceProvider.php`
- Test: `tests/Feature/Auth/AuthenticationTest.php`

**Interfaces:**
- `AuthenticateUser::__invoke(Request $request): ?User` returns a user only when credentials and account policy allow authentication.
- `FortifyServiceProvider` registers the action with `Fortify::authenticateUsing()` while retaining existing rate limiting and views.

- [ ] **Step 1: Add failing feature tests**

Cover these scenarios using `User::factory()` and `UserStatus`: soft-deleted user rejected, disabled user rejected, manual suspension rejected, wrong password increments the counter, threshold sets `status` and `suspended_until`, active user with correct password authenticates, successful login clears the counter, and expired automatic suspension is restored.

Use `Carbon::setTestNow()` for expiry assertions and `Config::set('auth.login_security.max_failed_attempts', 2)` / `Config::set('auth.login_security.suspension_minutes', 15)` for deterministic thresholds.

- [ ] **Step 2: Run the new tests to verify failure**

Run:

```bash
php artisan test --compact tests/Feature/Auth/AuthenticationTest.php
```

Expected: FAIL because Fortify currently does not enforce account status or persist failed-password state.

- [ ] **Step 3: Implement the action**

Implement the action so it:

```php
public function __invoke(Request $request): ?User
```

It must query with `withTrashed()`, compare the configured username field, reject deleted/non-active accounts, restore only expired automatic suspensions, verify the password with `Hash::check()`, and use a transaction with `lockForUpdate()` to update failure state. Return `null` for every rejected attempt so Fortify emits its normal generic failure response.

- [ ] **Step 4: Register the action in Fortify**

Register it from `configureActions()` using `Fortify::authenticateUsing(fn (Request $request): ?User => app(AuthenticateUser::class)($request));`. Preserve `ResetUserPassword` registration and the existing login limiter.

- [ ] **Step 5: Run the focused authentication tests**

Run:

```bash
php artisan test --compact tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/UuidUserAuthenticationTest.php
```

Expected: PASS, including existing valid-login, invalid-password, 2FA, UUID, and rate-limit coverage.

- [ ] **Step 6: Format PHP files**

Run:

```bash
vendor/bin/pint --dirty --format agent
```

### Task 4: Complete regression coverage and verification

**Files:**
- Modify: `tests/Feature/Auth/AuthenticationTest.php`
- Modify: `tests/Unit/Factories/UserFactoryTest.php` if factory expectations require explicit new defaults

**Interfaces:**
- Produces executable acceptance coverage for all approved policy rules without changing frontend behavior.

- [ ] **Step 1: Add edge-case assertions**

Verify manual suspension with `suspended_until=null` remains suspended, an expired automatic suspension resets to active before a valid password can authenticate, and deleted/disabled/manual-suspended attempts do not increment `failed_login_attempts`.

- [ ] **Step 2: Run the complete affected test set**

Run:

```bash
php artisan test --compact tests/Feature/Auth tests/Feature/UserSchemaTest.php tests/Unit/Models/UserTest.php tests/Unit/Factories/UserFactoryTest.php
```

Expected: PASS.

- [ ] **Step 3: Run static and formatting verification**

Run:

```bash
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --memory-limit=1G
```

Expected: no formatting changes needed after Pint and no new static-analysis errors.

- [ ] **Step 4: Review the diff against the spec**

Confirm every acceptance criterion in `docs/superpowers/specs/2026-07-13-user-login-account-status-and-suspension-design.md` is covered by implementation or a test, and confirm no passwords, account-existence details, or unrelated UI behavior were added to responses.
