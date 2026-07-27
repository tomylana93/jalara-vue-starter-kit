# User Login Account Status and Suspension

## Goal

Prevent deleted and non-active users from authenticating, and temporarily suspend active users after a configurable number of incorrect passwords.

## Approved policy

- Soft-deleted users cannot log in.
- Users with `UserStatus::Disable` cannot log in.
- Users with `UserStatus::Suspend` cannot log in while `suspended_until` is in the future.
- A suspended user with a `null` `suspended_until` is treated as manually suspended and is not automatically restored.
- Active users are automatically suspended after 5 failed password attempts by default.
- Automatic suspension lasts 15 minutes by default.
- Threshold and duration are configurable through `config/auth.php` and environment variables.
- A successful login clears failed-login tracking and updates `last_login_at`.
- Existing Fortify username/email plus IP rate limiting remains enabled as an additional defense.

## Data model

Add the following columns to `users`:

- `failed_login_attempts`: unsigned integer, default `0`.
- `suspended_until`: nullable timestamp.

The existing `status` column remains the source of the account's active/disabled/suspended state. `suspended_until` distinguishes automatic temporary suspension from a manual suspension: automatic suspensions always have an expiry timestamp; manual suspensions have no expiry unless the administrative workflow explicitly supplies one.

## Authentication flow

Fortify's custom authentication callback/action will retrieve the user with trashed records included, so the application can reject deleted users explicitly. The flow is:

1. Find the user by the configured Fortify username field.
2. Reject a missing, soft-deleted, disabled, or manually suspended user without authenticating.
3. If an automatic suspension has expired, restore the user to `active`, clear `suspended_until`, and reset `failed_login_attempts`.
4. Check the password.
5. For an incorrect password, increment the user's failed-attempt counter. When it reaches the configured threshold, set `status` to `suspend` and `suspended_until` to the configured expiry time.
6. For a correct password, clear failed-attempt tracking and return the user to Fortify for normal session/2FA handling.

The counter update and status transition must be protected against concurrent login requests, using a transaction and row locking or an equivalent atomic update strategy.

## Configuration

Add these `config/auth.php` values:

```php
'login_security' => [
    'max_failed_attempts' => env('AUTH_LOGIN_MAX_FAILED_ATTEMPTS', 5),
    'suspension_minutes' => env('AUTH_LOGIN_SUSPENSION_MINUTES', 15),
],
```

No dependency changes are required.

## Error and information disclosure policy

Deleted, disabled, manually suspended, expired-automatic-suspension, nonexistent, and invalid-password attempts must not expose whether an account exists or disclose internal account state. They use the existing generic Fortify login failure behavior. The initial scope does not add a countdown or status-specific frontend message.

## Acceptance criteria

- Active users with correct credentials authenticate normally.
- Deleted users cannot authenticate.
- Disabled users cannot authenticate.
- Manually suspended users cannot authenticate.
- Failed attempts increment only for eligible active users.
- The configured threshold creates an automatic suspension.
- Automatic suspension blocks login until its expiry.
- Expired automatic suspension is restored on a subsequent attempt.
- Manual suspension is never restored automatically.
- Successful authentication resets failure tracking.
- `last_login_at` continues to be updated by the existing successful-login behavior.
- Existing Fortify rate limiting and authentication/2FA tests continue to pass.

## Out of scope

- Admin UI for changing user status.
- Email or notification on suspension.
- Password-reset policy changes.
- Permanent account lockout.
- IP-only or device-based lockout storage beyond Fortify's existing limiter.
