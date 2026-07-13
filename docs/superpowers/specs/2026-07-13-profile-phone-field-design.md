# Profile Phone Field Design

## Purpose

Allow an authenticated user to view, add, replace, or remove their own phone
number from Profile settings. The `users.phone` column already exists, is
nullable, and has a unique index; this change makes that existing user attribute
available through the self-service profile flow.

## Scope

### Included

- Add an optional **Phone number** input to the existing Profile settings form.
- Pre-fill it from the authenticated user's shared Inertia data.
- Submit it with the existing profile update request.
- Store a provided value, and persist an empty submission as `null` so a user
  can clear their phone number.
- Enforce uniqueness while allowing the authenticated user to retain their own
  existing phone number.
- Add request-level feature coverage for saving, clearing, and rejecting a
  duplicate phone number.

### Excluded

- Database migrations or index changes.
- E.164 conversion, country-specific formatting, SMS verification, or phone
  authentication.
- Changes to user administration, registration, or any other account fields.

## Design

The existing `Profile.vue` page will retain its Inertia `<Form>` and Wayfinder
controller action. A third field will follow the established label/input/error
pattern, using `type="tel"`, `name="phone"`, and the current user's phone value
as its default. The field is optional and does not introduce a new component or
layout pattern.

`ProfileUpdateRequest` will include `phone` in its validated profile-attribute
shape. Its validation rules will permit `null`, accept a string of at most 255
characters, and apply a `Rule::unique(User::class)->ignore($currentUserId)`
constraint. The request will normalize a missing or blank value to `null` before
passing the attributes to `UpdateUserProfile`. This prevents an empty string
from conflicting with the unique `users.phone` index and makes clearing the
field deterministic.

`UpdateUserProfile` will continue to fill the validated attributes and reset
email verification only when the email value changes. Phone-only updates will
not alter email-verification state.

## User-visible Behavior

- A user with a saved phone number sees it when opening Profile settings.
- A user may save a non-empty, unused phone number.
- A user may submit the same phone number already assigned to their account.
- A user may remove their phone number by submitting the field empty; the
  persisted value becomes `null`.
- A phone number already assigned to another user produces an inline `phone`
  validation error and leaves the existing value unchanged.
- Saving a phone number shows the existing success toast and redirects back to
  Profile settings.

## Error Handling and Security

- The server remains the source of truth for validation; client-side `tel`
  input is an input aid, not a validation guarantee.
- The existing authenticated profile-update route and ownership model remain
  unchanged: the request updates only `$request->user()`.
- Email verification is reset only for email changes, never for phone-only
  changes.

## Testing

Extend `tests/Feature/Settings/ProfileUpdateTest.php` using the existing Pest
style and model factories. Verify successful persistence of an optional phone
number, conversion of an empty phone submission to `null`, preservation of
email verification for a phone-only update, and validation failure for a phone
number used by another user.

Run the focused profile feature test, followed by Pint because the change
includes PHP files. No migration or dependency update is required.

## Acceptance Criteria

1. Profile settings displays an optional phone-number input populated from
   `auth.user.phone`.
2. A valid, unique phone number persists through `PATCH profile.update`.
3. An empty phone submission clears the persisted value to `null`.
4. A duplicate phone number is rejected with a field error.
5. A phone-only change does not reset `email_verified_at`.
6. Existing name and email update behavior remains covered and passing.
