# Form Component Standardization Design

## Goal

Standardize application form submissions on Inertia's Vue `<Form>` component,
remove native `required` validation, and repair the incomplete General Settings
form migration.

## Scope

- Replace any remaining application `useForm` usage with `<Form>` where the
  submission is a normal Inertia visit.
- Bring every application form into the existing `<Form>` conventions:
  Wayfinder-generated form configuration, named controls, slot-provided
  `errors` and `processing`, and server-provided validation messages.
- Complete the General Settings form so it submits the three settings values,
  displays the correct server validation errors, and no longer references the
  removed `form` helper.
- Remove only native `required` attributes from application form controls.
- Remove redundant per-control and per-error vertical margins in field groups
  that already use `gap-2`.

## Out of Scope

- Changes to Laravel Form Requests, Fortify validation, controller actions,
  routes, policies, or validation messages.
- Removing semantic input types, autocomplete metadata, OTP length limits, or
  client-side interaction state that is not validation.
- Reworking uploads, passkeys, two-factor setup, or standalone HTTP requests.
- Creating a new shared form-field component.

## Form Design

The `<Form>` component is the application default for page-level Inertia form
submissions. Its generated Wayfinder configuration supplies the endpoint and
HTTP method. Native controls provide request data through `name` and their
initial values through `default-value` (or the equivalent control API).

Each form consumes `errors` and `processing` from the component slot. Errors
are rendered through the existing `InputError` component and submission buttons
are disabled while processing. Laravel remains the single validation authority:
validation failures redirect back through Inertia and populate the slot's
`errors` object.

## General Settings Repair

The General Settings page will retain its existing generated controller form
configuration and page props. Its application-name input, description textarea,
and locale select will be converted from helper-managed bindings to named form
controls with the incoming settings as defaults. The form will expose the
`errors` and `processing` slot props, use those values for its three errors and
submit button, and close with `</Form>`.

## Validation and Interaction Rules

Remove every `required` attribute from application form controls. Retain input
types, autocomplete, placeholders, disabled processing state, OTP maximum
length, reset behavior, transforms, and error-clearing behavior where those
serve submission mechanics or user experience rather than native validation.

## Layout Rules

For the established `grid gap-2` field groups, remove the redundant `mt-1`
field margin and `mt-2` error margin. The container gap becomes the sole
vertical spacing authority between label, control, and error.

## Testing Design

Existing Laravel feature tests are the primary seam because they prove the
user-visible server validation contract independently of the form
implementation. Preserve and run the focused General Settings access and
validation tests. Frontend verification must type-check, lint, and build the
Vue application; it must also confirm no application `useForm` or `required`
attributes remain in the migrated form surfaces.

## Constraints

- Use Inertia v3 `<Form>` and its Vue slot API.
- Use Wayfinder-generated form bindings; do not hard-code request URLs.
- Do not add dependencies.
- Preserve the current dirty worktree outside the files in this refactor.
