# Precognitive Form Validation Design

## Goal

Give authenticated account-management forms immediate, server-authoritative
validation feedback and make every invalid control visibly and semantically
invalid.

## Scope

- Enable Inertia v3's built-in Laravel Precognition support on the Profile and
  General Settings `<Form>` components.
- Validate a changed field when it loses focus, using the existing Wayfinder
  form action as the endpoint and method source.
- Apply `aria-invalid` from Inertia's `invalid(field)` helper to each validated
  control, and keep the existing `InputError` message below that control.
- Use the existing invalid styles on `Input`, `Textarea`, and `SelectTrigger`.
- Protect Precognition endpoints with the same authentication and authorization
  middleware as their final submissions, plus an explicit low-rate throttle.

## Out of Scope

- Login, registration, password reset, password confirmation, two-factor,
  passkey, and other authentication flows.
- File/avatar Precognition validation. Avatar files remain excluded from
  validation requests and retain their current upload workflow.
- Duplicating Laravel validation rules in Vue, changing validation messages, or
  modifying final submission behavior.
- New dependencies, a custom form-field abstraction, success-state decoration,
  or validation requests while typing.

## Architecture

The existing Inertia `<Form>` is the single frontend form primitive. Its
Wayfinder-generated `*.form()` binding supplies the existing PATCH endpoint;
the form's Precognition slot helpers (`validate`, `invalid`, and
`validating`) use that same endpoint for field validation. The component
validates a changed field on `blur` and sets `:aria-invalid="invalid('field')"`
on the associated interactive control.

Laravel Form Requests remain the sole source of validation rules. The
`precognitive` route middleware runs validation and short-circuits before the
controller action, so successful field checks never update the profile or site
settings. Final form submission still follows the normal authenticated,
authorized PATCH route and remains authoritative.

## Form Behaviour

### Profile

The Profile form validates `name`, `email`, and `phone` on blur. It does not
validate the hidden temporary-avatar upload identifier or the avatar uploader.
Each of the three `Input` controls receives its own `aria-invalid` state and
continues to render the corresponding `InputError` message.

### General Settings

The General Settings form validates `site_name`, `site_description`, and
`site_locale` on blur. `Input` and `Textarea` receive `aria-invalid` directly.
Because the Select's interactive element is `SelectTrigger`, the invalid state
is applied there rather than to the Select wrapper. Each field preserves its
current translated label, placeholder, default value, and `InputError` message.

### Request Timing

Validation is initiated only after a user leaves a changed field. Set the
Inertia form's validation timeout to 750 ms so quick correction and re-blur
events are coalesced without delaying the initial feedback unnecessarily. Do
not show a global "validating" indicator; the control's error state changes
only after a response is received.

## Security

- Precognition is limited to authenticated routes. General Settings additionally
  retains its existing `verified` and policy authorization middleware.
- Add the `precognitive` and `throttle:30,1` middleware to the two PATCH routes.
  Precognition uses the existing routes rather than exposing a separate public
  validation endpoint.
- Do not enable the feature on credential or account-recovery forms. This avoids
  repeatedly transmitting passwords and prevents early validation responses
  from becoming an account-enumeration oracle.
- The full submitted payload may accompany field validation; the existing
  profile Form Request must therefore continue to authorize the current user
  and validate only the expected fields.
- Do not enable `validate-files`; uploads remain outside Precognition requests.

## Accessibility and Error States

`aria-invalid="true"` conveys the invalid state to assistive technology and
activates the destructive border/ring already present in the shared controls.
The visible `InputError` remains the human-readable error presentation. The
implementation should add `aria-describedby` only if the existing error
component is upgraded to emit stable field-specific IDs; that change is not
required for this scoped refactor.

## Testing Design

Feature tests will prove that each protected PATCH route accepts a precognitive
request, returns validation errors for invalid data, does not execute the
controller side effects, and rejects unauthenticated or unauthorized callers.
Existing final-submission tests remain in place to prove the normal PATCH
contract is unchanged.

Frontend coverage will assert the two form templates expose Precognition slot
helpers, validate the intended fields on blur, and bind the matching
`aria-invalid` state. Run the targeted Pest tests, Prettier, type checking,
linting, and Vite build.

## Constraints

- Laravel 13, Inertia v3 Vue `<Form>`, Wayfinder, and the current shared UI
  controls only; add no dependencies.
- Use Wayfinder-generated form configuration and never hard-code URLs.
- Retain all existing validation rules, translations, submission endpoints, and
  final submission success/error behavior.
- Preserve unrelated worktree changes.
