# Remove User Self-Deletion Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or **superpowers:executing-plans** to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove the user self-deletion capability and all of its application surfaces without changing profile editing or other authentication behavior.

**Architecture:** Delete the dedicated deletion UI, request, action, controller method, route, and tests. Keep `ProfileController` as the profile edit/update adapter, preserve the existing `profile.edit` and `profile.update` contracts, and regenerate Wayfinder bindings from the remaining routes and controller methods.

**Tech Stack:** PHP 8.5, Laravel 13, Inertia v3, Vue 3, Wayfinder, Pest 4, Laravel Pint, ESLint, Prettier, TypeScript.

## Global Constraints

- Remove the user-facing self-delete flow completely, including the reusable `DeleteUserAccount` action.
- Do not introduce an administrative deletion workflow or replacement deletion abstraction.
- Preserve profile display/update behavior and the `profile.edit` and `profile.update` route names.
- Do not change database schema, dependencies, authentication features, or unrelated settings behavior.
- Delete obsolete deletion tests rather than replacing them with tests for behavior that no longer exists.
- Regenerate Wayfinder output after removing the backend route/controller method.
- If PHP files are modified, run `vendor/bin/pint --dirty --format agent`.

---

### Task 1: Remove backend self-deletion capability and obsolete PHP tests

**Files:**
- Modify: `app/Http/Controllers/Settings/ProfileController.php`
- Modify: `routes/settings.php`
- Delete: `app/Actions/DeleteUserAccount.php`
- Delete: `app/Http/Requests/Settings/ProfileDeleteRequest.php`
- Delete: `tests/Unit/Auth/Actions/DeleteUserAccountTest.php`
- Modify: `tests/Feature/Settings/ProfileUpdateTest.php`

**Interfaces:**
- Preserves: `ProfileController::edit(Request $request): Inertia\Response`.
- Preserves: `ProfileController::update(ProfileUpdateRequest $request, UpdateUserProfile $updateUserProfile): RedirectResponse`.
- Removes: `ProfileController::destroy(ProfileDeleteRequest $request, DeleteUserAccount $deleteUserAccount): RedirectResponse`.
- Removes: `DELETE /settings/profile` named route `profile.destroy`.

- [ ] **Step 1: Remove the deletion feature assertions**

Delete the `user can delete their account` and `correct password must be provided to delete account` tests from `tests/Feature/Settings/ProfileUpdateTest.php`. Leave the profile page and profile update tests unchanged.

- [ ] **Step 2: Remove the backend route and controller deletion code**

In `routes/settings.php`, remove the `Route::delete('settings/profile', ...)` declaration and leave the remaining verified settings routes intact.

In `ProfileController.php`, remove the `DeleteUserAccount` and `ProfileDeleteRequest` imports, remove the `Illuminate\Facades\Auth` import, and remove the `destroy()` method. Do not alter `edit()` or `update()`.

- [ ] **Step 3: Delete deletion-only PHP files and unit test**

Delete `app/Actions/DeleteUserAccount.php`, `app/Http/Requests/Settings/ProfileDeleteRequest.php`, and `tests/Unit/Auth/Actions/DeleteUserAccountTest.php`. No replacement action or request is needed.

- [ ] **Step 4: Run the focused backend tests**

Run:

```bash
php artisan test --compact tests/Feature/Settings/ProfileUpdateTest.php tests/Feature/Settings/SecurityTest.php tests/Unit/Auth/Actions/UpdateUserProfileTest.php
```

Expected: PASS. The profile feature suite should retain coverage for displaying and updating profiles, with no deletion tests remaining.

- [ ] **Step 5: Format modified PHP**

Run:

```bash
vendor/bin/pint --dirty --format agent
```

Expected: Pint completes successfully and reports no remaining formatting changes for the modified PHP files.

### Task 2: Remove the profile deletion UI and regenerate frontend route bindings

**Files:**
- Modify: `resources/js/pages/settings/Profile.vue`
- Delete: `resources/js/components/DeleteUser.vue`
- Modify: `resources/js/actions/App/Http/Controllers/Settings/ProfileController.ts`
- Modify: `resources/js/routes/profile/index.ts`
- Modify: `resources/js/actions/App/Http/Controllers/Settings/index.ts` only if regeneration changes its exports

**Interfaces:**
- Preserves: `ProfileController.update.form()` used by `resources/js/pages/settings/Profile.vue`.
- Preserves: `edit()` from `resources/js/routes/profile` used by profile breadcrumbs.
- Removes: `ProfileController.destroy.form()` and the generated `profile.destroy` route helper.

- [ ] **Step 1: Remove the deletion component from the profile page**

In `resources/js/pages/settings/Profile.vue`, remove the `DeleteUser` import and the `<DeleteUser />` element. Keep the existing `ProfileController` import because profile updates still use it, and keep the profile form/breadcrumb imports unchanged.

- [ ] **Step 2: Regenerate Wayfinder bindings from the updated backend**

Run:

```bash
php artisan wayfinder:generate
```

Expected: generated bindings no longer contain `ProfileController.destroy` or the `profile.destroy` route helper, while `edit` and `update` remain available.

- [ ] **Step 3: Confirm no frontend references remain**

Run:

```bash
rg -n "DeleteUser|ProfileDeleteRequest|DeleteUserAccount|profile\.destroy|ProfileController\.destroy" app routes resources/js tests
```

Expected: no matches. If generated files contain stale deletion references, rerun Wayfinder generation and inspect the route/controller source before making any manual generated-file edit.

- [ ] **Step 4: Run frontend checks**

Run:

```bash
pnpm run format:check
pnpm run lint:check
pnpm run types:check
```

Expected: all three commands pass, with the profile page compiling without the removed component or route method.

### Task 3: Verify the route contract and final diff

**Files:**
- Inspect: `routes/settings.php`
- Inspect: `app/Http/Controllers/Settings/ProfileController.php`
- Inspect: `resources/js/pages/settings/Profile.vue`
- Inspect: generated Wayfinder files under `resources/js/actions/` and `resources/js/routes/`

**Interfaces:**
- Preserves: profile edit/update routes and all unrelated authentication/settings routes.
- Removes: every user self-deletion entry point and implementation artifact.

- [ ] **Step 1: Verify the route list**

Run:

```bash
php artisan route:list --name=profile --except-vendor
```

Expected: `profile.edit` and `profile.update` are listed, and `profile.destroy` is absent.

- [ ] **Step 2: Run the complete relevant test groups**

Run:

```bash
php artisan test --compact tests/Feature/Settings tests/Feature/Auth tests/Unit/Auth/Actions
```

Expected: PASS for the remaining settings, authentication, and action tests.

- [ ] **Step 3: Review the final diff and repository references**

Run:

```bash
git diff --check
git diff --stat
git status --short
rg -n "delete account|DeleteUser|profile\.destroy|DeleteUserAccount|ProfileDeleteRequest" app routes resources/js tests
```

Expected: the diff contains only the approved removal and generated binding updates; the final search has no matches in application or test code. Existing historical specs/plans may mention the old action as historical context and do not need rewriting.

- [ ] **Step 4: Commit the implementation**

```bash
git add app/Actions/DeleteUserAccount.php app/Http/Requests/Settings/ProfileDeleteRequest.php app/Http/Controllers/Settings/ProfileController.php routes/settings.php tests/Unit/Auth/Actions/DeleteUserAccountTest.php tests/Feature/Settings/ProfileUpdateTest.php resources/js/pages/settings/Profile.vue resources/js/components/DeleteUser.vue resources/js/actions resources/js/routes
git commit -m "refactor: remove user self-deletion"
```
