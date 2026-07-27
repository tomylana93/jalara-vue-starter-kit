# Login Audit Timestamp and Suspension Race Fix Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Record `last_login_at` only after completed authentication and preserve an administrator's concurrent account-status change during failed-password handling.

**Architecture:** A `Login` event listener updates the timestamp after Laravel's session guard authenticates the user, including completed Fortify 2FA flows. The existing `AuthenticateUser` action retains its transaction, but treats the locked row—not the earlier query result—as authoritative before it increments a failure counter or creates an automatic suspension.

**Tech Stack:** PHP 8.5, Laravel 13, Laravel Fortify 1, Eloquent, SQLite, Pest 4.

## Global Constraints

- Set `last_login_at` only after a completed session login, including successful 2FA.
- Do not change account status, authentication responses, or failure counters from the login-audit listener.
- Only active, non-deleted locked records may receive failed-password mutations.
- Retain the current generic Fortify login failure response and automatic-suspension thresholds.
- The original users migration is intentionally modified; run `php artisan migrate:fresh --no-interaction` locally before verification. This deletes all local database data.
- Do not add dependencies.

---

### Task 1: Add completed-login audit coverage

**Files:**

- Modify: `tests/Feature/Auth/AuthenticationTest.php`
- Modify: `tests/Feature/Auth/TwoFactorChallengeTest.php`

**Interfaces:**

- Consumes the existing `last_login_at` nullable datetime cast on `User`.
- Defines the expected behavior later produced by `UpdateLastLoginAt::handle(Login $event): void`.

- [ ] **Step 1: Write the failing password-only and failed-login tests**

In `tests/Feature/Auth/AuthenticationTest.php`, freeze time, submit valid credentials, and assert the authenticated user's fresh `last_login_at` equals `now()`. Add a separate incorrect-password test asserting the fresh value remains `null`.

```php
$this->freezeTime();
$user = User::factory()->create(['last_login_at' => null]);

$this->post(route('login.store'), [
    'email' => $user->email,
    'password' => 'password',
]);

$this->assertAuthenticatedAs($user);
expect($user->fresh()->last_login_at)->toEqual(now());
```

- [ ] **Step 2: Write the failing completed-2FA test**

In `tests/Feature/Auth/TwoFactorChallengeTest.php`, enable confirmed two-factor authentication, create `User::factory()->withTwoFactor()`, and freeze time. After the password POST, assert the user is still a guest and `last_login_at` is `null`. Complete the challenge through the existing recovery code and assert the user is authenticated and the fresh timestamp equals `now()`.

```php
$this->post(route('login'), [
    'email' => $user->email,
    'password' => 'password',
]);

$this->assertGuest();
expect($user->fresh()->last_login_at)->toBeNull();

$this->post(route('two-factor.login.store'), [
    'recovery_code' => 'recovery-code-1',
]);

$this->assertAuthenticatedAs($user);
expect($user->fresh()->last_login_at)->toEqual(now());
```

- [ ] **Step 3: Run the audit tests to verify failure**

Run:

```bash
php artisan test --compact tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/TwoFactorChallengeTest.php
```

Expected: FAIL because no login-event listener updates `last_login_at`.

### Task 2: Add the completed-login event listener

**Files:**

- Create: `app/Listeners/UpdateLastLoginAt.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/Auth/AuthenticationTest.php`
- Test: `tests/Feature/Auth/TwoFactorChallengeTest.php`

**Interfaces:**

- Produces `UpdateLastLoginAt::handle(Login $event): void`.
- `AppServiceProvider::boot()` registers `Login::class` with `UpdateLastLoginAt::class`.

- [ ] **Step 1: Create the listener**

Create `app/Listeners/UpdateLastLoginAt.php` with the following behavior. It ignores non-application authenticatable users and updates only the timestamp.

```php
<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class UpdateLastLoginAt
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill([
            'last_login_at' => now(),
        ])->save();
    }
}
```

- [ ] **Step 2: Register the listener in the application provider**

Add these imports and listener registration to `AppServiceProvider::boot()`:

```php
use App\Listeners\UpdateLastLoginAt;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;

Event::listen(Login::class, UpdateLastLoginAt::class);
```

- [ ] **Step 3: Run the audit tests to verify success**

Run:

```bash
php artisan test --compact tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/TwoFactorChallengeTest.php
```

Expected: PASS. Password-only login and completed 2FA set the timestamp; invalid credentials and the pre-2FA state do not.

- [ ] **Step 4: Format the changed PHP files**

Run:

```bash
vendor/bin/pint --dirty --format agent
```

### Task 3: Protect administrator status changes within the failed-attempt transaction

**Files:**

- Modify: `app/Actions/Fortify/AuthenticateUser.php`
- Modify: `tests/Feature/Auth/AuthenticationTest.php`

**Interfaces:**

- `AuthenticateUser::registerFailedAttempt(User $user): void` leaves a locked non-active or soft-deleted user unchanged.

- [ ] **Step 1: Write the failing stale-request regression test**

In the authentication feature test named `stale failed login does not overwrite an administrator disable`, simulate a status change after `AuthenticateUser` has loaded an active user but before its locked lookup. Set `auth.login_security.max_failed_attempts` to `5`, create the user with `failed_login_attempts` equal to `4`, then register a temporary `retrieved` callback that changes the persisted user to `UserStatus::Disable` on the first lookup. Submit an incorrect password and assert the persisted record remains disabled with `failed_login_attempts` equal to `4` and `suspended_until` equal to `null`.

```php
Config::set('auth.login_security.max_failed_attempts', 5);

$user = User::factory()->create(['failed_login_attempts' => 4]);
$changed = false;

User::retrieved(function (User $retrieved) use ($user, &$changed): void {
    if (! $changed && $retrieved->is($user)) {
        $changed = true;

        User::withoutEvents(fn () => User::whereKey($user)->update([
            'status' => UserStatus::Disable,
        ]));
    }
});

$this->post(route('login.store'), [
    'email' => $user->email,
    'password' => 'wrong-password',
]);
```

The final assertions must use `$user->fresh()` and expect `Disable`, `4`, and `null`; the test must also assert the request left the user unauthenticated.

- [ ] **Step 2: Run the race-regression test to verify failure**

Run:

```bash
php artisan test --compact --filter="stale failed login does not overwrite an administrator disable" tests/Feature/Auth/AuthenticationTest.php
```

Expected: FAIL because the current locked lookup increments the counter and can replace `Disable` with `Suspend` at the threshold.

- [ ] **Step 3: Re-check the locked record before mutation**

In `registerFailedAttempt()`, immediately after the null check, return unless the locked record is not trashed and has `UserStatus::Active`:

```php
if (! $locked || $locked->trashed() || $locked->status !== UserStatus::Active) {
    return;
}
```

Keep the existing counter increment, configured threshold, timestamp calculation, transaction, and `lockForUpdate()` call unchanged.

- [ ] **Step 4: Run the race-regression test to verify success**

Run the same filtered command and expect PASS.

- [ ] **Step 5: Run the automatic-suspension regression tests**

Run:

```bash
php artisan test --compact --filter="incorrect password|failed-attempt threshold|ineligible accounts|expired automatic suspension" tests/Feature/Auth/AuthenticationTest.php
```

Expected: PASS; active accounts retain the existing counter and suspension behavior.

- [ ] **Step 6: Format the changed PHP files**

Run:

```bash
vendor/bin/pint --dirty --format agent
```

### Task 4: Rebuild the local database and run final verification

**Files:**

- No additional source files.

**Interfaces:**

- Validates the schema produced by the intentionally edited original users migration.

- [ ] **Step 1: Rebuild the local database**

Run:

```bash
php artisan migrate:fresh --no-interaction
```

Expected: the database is dropped and recreated with `failed_login_attempts` and `suspended_until` on `users`. Do not run this command against an environment containing data that must be retained.

- [ ] **Step 2: Run the affected test suites**

Run:

```bash
php artisan test --compact tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/TwoFactorChallengeTest.php tests/Feature/Auth/UuidUserAuthenticationTest.php tests/Feature/UserSchemaTest.php tests/Unit/Models/UserTest.php tests/Unit/Factories/UserFactoryTest.php
```

Expected: PASS.

- [ ] **Step 3: Run format and static analysis**

Run:

```bash
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --memory-limit=1G
```

Expected: Pint reports no remaining formatting changes and PHPStan reports no new errors.

- [ ] **Step 4: Commit the implementation**

Run the following commands:

```bash
git add app/Actions/Fortify/AuthenticateUser.php app/Listeners/UpdateLastLoginAt.php app/Providers/AppServiceProvider.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/TwoFactorChallengeTest.php
git commit -m "fix: audit completed logins and preserve account status"
```
