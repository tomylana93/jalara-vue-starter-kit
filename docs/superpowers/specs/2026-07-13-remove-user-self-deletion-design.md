# Remove User Self-Deletion Design

## Goal

Remove the user self-deletion mechanism completely so authenticated users can no longer delete their own accounts from profile settings or through the existing profile deletion endpoint.

## Scope

Remove every application surface dedicated to user-initiated account deletion:

- The profile settings deletion UI.
- The `profile.destroy` route.
- The `ProfileController::destroy()` HTTP action.
- Password confirmation validation specific to profile deletion.
- The `DeleteUserAccount` application action.
- Unit and feature tests whose purpose is to verify self-deletion.

Profile editing, password changes, two-factor authentication, email verification, login/logout, and other account settings remain unchanged.

This refactor does not introduce an administrative deletion workflow. No general-purpose user deletion action remains after the change.

## Architecture

The profile settings page will contain only profile information editing. `ProfileController` will retain `edit()` and `update()` as its complete public surface for profile settings.

The settings routes will continue to expose profile read/update behavior under the existing `profile.edit` and `profile.update` route names, while removing `profile.destroy`. Wayfinder-generated frontend bindings must be regenerated so deleted backend routes and controller methods are not callable from the frontend.

The deletion-specific request, action, Vue component, and tests will be removed rather than left as unused compatibility code. No replacement abstraction is required because the requested behavior is removal, not relocation.

## Data Flow and Security

After the refactor:

1. A user can load the profile settings page.
2. The page can submit profile name and email updates through `profile.update`.
3. No profile deletion control is rendered.
4. No `DELETE settings/profile` route is registered.
5. No controller or application action accepts a user-driven deletion request.

Existing authentication and authorization middleware for the remaining settings routes is preserved. Removing the route prevents the previous password-confirmed self-delete request from reaching application code.

## Testing Strategy

Update the existing settings feature coverage by removing tests that assert successful account deletion and incorrect-password deletion protection. Keep profile display and profile update tests unchanged.

Delete the unit test for `DeleteUserAccount` together with the action it covers.

Run the focused profile/settings tests and the relevant authentication tests. Verify the route list does not contain `profile.destroy`, and run Laravel Pint for any remaining modified PHP files.

## Migration Strategy

1. Remove the deletion UI and its frontend imports.
2. Remove the deletion route and controller dependencies/method.
3. Delete the deletion request and action classes.
4. Remove obsolete unit and feature tests.
5. Regenerate Wayfinder bindings.
6. Run focused tests, route verification, and formatting.

No database migration, dependency change, data transformation, or user-data cleanup is required. Existing accounts and all existing profile/settings data remain intact.

## Completion Criteria

- `DeleteUser.vue` no longer exists or is imported.
- `ProfileController` has no deletion method or `DeleteUserAccount`/`ProfileDeleteRequest` dependency.
- `profile.destroy` is absent from `routes/settings.php` and the generated route bindings.
- `ProfileDeleteRequest` and `DeleteUserAccount` no longer exist.
- Self-deletion unit and feature tests are removed.
- Profile display and profile update behavior continue to pass their tests.
- No new dependency, migration, admin workflow, or unrelated refactor is introduced.
