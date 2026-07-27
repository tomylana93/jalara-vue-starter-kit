# Login Audit Timestamp and Suspension Race Fix

## Goal

Record `last_login_at` only after a user has fully authenticated, and prevent a stale failed-password request from overwriting an administrator's disable or manual-suspension decision.

## Scope

This follow-up changes only the two reviewed issues:

1. Write `last_login_at` after completed authentication.
2. Re-check eligibility after acquiring the failed-attempt row lock.

The project continues to modify the original users migration. Development and test databases must be rebuilt with `php artisan migrate:fresh --no-interaction`; this command drops all database data.

## Completed-login timestamp

Register a dedicated listener for Laravel's `Illuminate\Auth\Events\Login` event. When the event user is `App\Models\User`, update only `last_login_at` to `now()`.

This event-driven boundary is required because Fortify invokes `AuthenticateUser` after password verification but before a configured two-factor challenge has been completed. The listener therefore records a timestamp only when the session guard has actually logged the user in, whether that is a password-only login or a completed 2FA login.

The listener does not change account status, failure counters, remember tokens, or authentication responses.

## Failed-attempt concurrency rule

`AuthenticateUser::registerFailedAttempt()` already locks the matching user row. After acquiring the lock it must verify that the freshly locked record is still eligible to receive a failed-password count:

- The record exists and is not soft deleted.
- Its status is `UserStatus::Active`.

If either condition is false, return without changing `failed_login_attempts`, `status`, or `suspended_until`. This preserves an administrator's concurrent disable or manual suspension instead of allowing a stale request to replace it with an automatic suspension.

The existing transition remains unchanged for an active locked record: increment the counter and, once the configured threshold is met, set `status` to `suspend` and set `suspended_until`.

## Acceptance criteria

- A password-only login sets `last_login_at`.
- A 2FA-enabled user does not receive `last_login_at` until the second-factor challenge succeeds.
- Failed or blocked login attempts do not update `last_login_at`.
- After the lock is acquired, disabled, manually suspended, or soft-deleted users are not mutated by failed-attempt handling.
- Existing automatic suspension behavior remains unchanged for active users.
- The affected feature tests pass after `php artisan migrate:fresh --no-interaction`.

## Out of scope

- Production migration strategy for already-migrated databases.
- Login history or IP/device audit records.
- Changes to the generic login failure response.
- Changes to suspension thresholds or duration.
