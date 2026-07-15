# Precognitive Form Validation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add secure Laravel Precognition feedback and visible invalid states to the Profile and General Settings forms without altering their final-submit contracts.

**Architecture:** The existing Inertia v3 `<Form>` components continue to receive Wayfinder-generated PATCH configuration. The two PATCH routes gain Laravel's `precognitive` middleware and `throttle:30,1`; field-blur handlers call the `<Form>` slot's `validate(field)` helper, and its `invalid(field)` result is forwarded to the actual control's `aria-invalid` attribute. Laravel Form Requests remain the only validation-rule source and Precognition short-circuits before either controller action executes.

**Tech Stack:** Laravel 13, Inertia v3 Vue, Wayfinder, Vue 3, Tailwind CSS v4, Pest 4, ESLint, Prettier, vue-tsc, Vite.

## Global Constraints

- Scope Precognition strictly to `profile.update` and `settings.general.update`; do not add it to login, registration, password reset, password confirmation, two-factor, passkey, or avatar-upload flows.
- Preserve the existing `auth`, `verified`, and General Settings policy middleware. Add `precognitive` and `throttle:30,1` only to the two existing PATCH routes.
- Use generated Wayfinder form bindings; do not hard-code request paths or methods and do not add dependencies.
- Validate only `name`, `email`, and `phone` in Profile; validate only `site_name`, `site_description`, and `site_locale` in General Settings.
- Trigger validation on `blur`, set `validation-timeout="750"`, leave file validation disabled, and do not show valid/success decoration.
- Bind `aria-invalid` to Inertia's `invalid(field)` helper on the interactive control: `Input`, `Textarea`, or `SelectTrigger`.
- Retain all existing Form Request rules, translations, default values, `InputError` rendering, and final-submit behavior.
- Do not change generated `resources/js/actions` or `resources/js/routes` files. Preserve unrelated worktree changes.

---

## File Structure

- `bootstrap/app.php` — extend the app's custom `shouldRenderJsonWhen` predicate with `|| $request->isPrecognitive()`. This app overrides the framework default to render JSON only for `api/*` or `expectsJson()` requests; without this branch, precognitive validation failures are redirected (302) instead of returning JSON 422, breaking the Precognition client contract. Regression coverage: `tests/Feature/Profile/ProfileUpdateTest.php` — "precognitive validation failures render as json instead of a redirect". `isPrecognitive()` reads a request attribute set only by the `precognitive` middleware, so this is a verified no-op for all non-precognitive flows.
- `routes/profile.php` — attach Precognition and throttling to the authenticated profile PATCH route.
- `routes/settings.php` — attach Precognition and throttling to the authenticated, verified, policy-protected general-settings PATCH route.
- `tests/Feature/Profile/ProfileUpdateTest.php` — prove profile Precognition validates and never updates the user.
- `tests/Feature/Settings/GeneralSettingsAccessTest.php` — prove authorized settings Precognition validates and never persists settings, while unauthorized requests stay forbidden.
- `resources/js/pages/Profile.vue` — consume Precognition slot helpers and bind blur/error state for name, email, and phone.
- `resources/js/pages/settings/general/Edit.vue` — consume Precognition slot helpers and bind blur/error state for site name, description, and locale.

### Task 1: Lock down the profile Precognition route with failing feature tests

**Files:**
- Modify: `tests/Feature/Profile/ProfileUpdateTest.php`
- Modify: `routes/profile.php`

**Interfaces:**
- Consumes: `PATCH profile.update`, `ProfileUpdateRequest`, `ProfileController::update()`, and Laravel test helpers `withPrecognition()` / `assertSuccessfulPrecognition()`.
- Produces: a protected precognitive PATCH route that returns field errors without calling the controller and successful precognition without updating profile data.

- [ ] **Step 1: Add failing invalid-field Precognition coverage**

Append this test to `tests/Feature/Profile/ProfileUpdateTest.php`:

```php
test('profile precognition returns field validation errors without updating the user', function (): void {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $this->actingAs($user)
        ->withPrecognition()
        ->withHeader('Precognition-Validate-Only', 'name')
        ->patch(route('profile.update'), [
            'name' => '',
            'email' => $user->email,
            'phone' => '',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    expect($user->refresh())
        ->name->toBe('Original Name')
        ->email->toBe('original@example.com');
});
```

- [ ] **Step 2: Add failing successful Precognition coverage**

Append this test directly after the previous one:

```php
test('profile precognition succeeds without updating the user', function (): void {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $this->actingAs($user)
        ->withPrecognition()
        ->withHeader('Precognition-Validate-Only', 'name')
        ->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'phone' => '',
        ])
        ->assertSuccessfulPrecognition();

    expect($user->refresh()->name)->toBe('Original Name');
});
```

- [ ] **Step 3: Run the two new tests to verify they fail before middleware is enabled**

Run:

```bash
php artisan test --compact tests/Feature/Profile/ProfileUpdateTest.php --filter=precognition
```

Expected: FAIL. Before the route has `precognitive`, the normal controller/redirect path does not provide Precognition responses and the valid request can reach the update action.

- [ ] **Step 4: Add the Precognition and throttle middleware to only the profile update route**

Replace the final profile route declaration in `routes/profile.php` with:

```php
Route::patch('profile', [ProfileController::class, 'update'])
    ->middleware(['precognitive', 'throttle:30,1'])
    ->name('profile.update');
```

Keep the outer `Route::middleware(['auth'])` group and all avatar routes unchanged.

- [ ] **Step 5: Run the profile Precognition tests again**

Run:

```bash
php artisan test --compact tests/Feature/Profile/ProfileUpdateTest.php --filter=precognition
```

Expected: PASS. Invalid `name` returns a JSON validation error, successful validation includes `Precognition-Success: true`, and neither request mutates the user.

- [ ] **Step 6: Commit the profile route protection and tests**

```bash
git add routes/profile.php tests/Feature/Profile/ProfileUpdateTest.php
git commit -m "feat: add profile precognition validation"
```

### Task 2: Protect and test the General Settings Precognition route

**Files:**
- Modify: `tests/Feature/Settings/GeneralSettingsAccessTest.php`
- Modify: `routes/settings.php`

**Interfaces:**
- Consumes: `PATCH settings.general.update`, `UpdateGeneralSettingsRequest`, `GeneralSettingsPolicy`, `Permission::ManageSettings`, and `GeneralSettings`.
- Produces: precognitive validation usable only by authenticated, verified users with `manage settings`, with no settings persistence on validation-only requests.

- [ ] **Step 1: Add failing authorization and invalid-field Precognition tests**

Append these tests to `tests/Feature/Settings/GeneralSettingsAccessTest.php`:

```php
test('a user without manage settings cannot make a precognitive general settings request', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withPrecognition()
        ->withHeader('Precognition-Validate-Only', 'site_name')
        ->patch(route('settings.general.update'), [
            'site_name' => '',
            'site_description' => '',
            'site_locale' => SiteLocale::English->value,
        ])
        ->assertForbidden();
});

test('general settings precognition returns field errors without persisting settings', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::ManageSettings->value);
    $settings = app(GeneralSettings::class);
    $originalSiteName = $settings->site_name;

    $this->actingAs($user)
        ->withPrecognition()
        ->withHeader('Precognition-Validate-Only', 'site_name')
        ->patch(route('settings.general.update'), [
            'site_name' => '',
            'site_description' => '',
            'site_locale' => SiteLocale::English->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('site_name');

    expect(app(GeneralSettings::class)->site_name)->toBe($originalSiteName);
});
```

- [ ] **Step 2: Add failing successful-validation/no-persistence coverage**

Append this test after the invalid-field test:

```php
test('general settings precognition succeeds without persisting settings', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::ManageSettings->value);
    $settings = app(GeneralSettings::class);
    $originalSiteName = $settings->site_name;

    $this->actingAs($user)
        ->withPrecognition()
        ->withHeader('Precognition-Validate-Only', 'site_name')
        ->patch(route('settings.general.update'), [
            'site_name' => 'Validated but not saved',
            'site_description' => '',
            'site_locale' => SiteLocale::English->value,
        ])
        ->assertSuccessfulPrecognition();

    expect(app(GeneralSettings::class)->site_name)->toBe($originalSiteName);
});
```

- [ ] **Step 3: Verify the new tests fail before the settings route changes**

Run:

```bash
php artisan test --compact tests/Feature/Settings/GeneralSettingsAccessTest.php --filter=precognition
```

Expected: FAIL for the authorized validation requests because the existing route does not yet activate Laravel Precognition.

- [ ] **Step 4: Attach the Precognition and throttle middleware without weakening the policy**

Change the update route in `routes/settings.php` to:

```php
Route::patch('settings/general', [GeneralSettingsController::class, 'update'])
    ->middleware(['precognitive', 'throttle:30,1'])
    ->name('settings.general.update')
    ->can('update', GeneralSettings::class);
```

Do not alter the surrounding `['auth', 'verified']` group or the existing `can('update', GeneralSettings::class)` middleware.

- [ ] **Step 5: Run the General Settings tests to prove both normal and precognitive contracts**

Run:

```bash
php artisan test --compact tests/Feature/Settings/GeneralSettingsAccessTest.php
```

Expected: PASS. Unauthorized Precognition remains forbidden; invalid authorized requests return field errors; successful precognition does not write settings; existing normal PATCH tests retain their redirect and persistence behavior.

- [ ] **Step 6: Commit the protected settings route and tests**

```bash
git add routes/settings.php tests/Feature/Settings/GeneralSettingsAccessTest.php
git commit -m "feat: add settings precognition validation"
```

### Task 3: Add Profile Precognition interactions and invalid control state

**Files:**
- Modify: `resources/js/pages/Profile.vue`
- Test: `tests/Feature/Profile/ProfileUpdateTest.php`

**Interfaces:**
- Consumes: `ProfileController.update.form()`, Inertia `<Form>` slot helpers `errors`, `invalid`, `validate`, `processing`, and the existing `Input` / `InputError` components.
- Produces: blur-triggered validation and `aria-invalid` state for name, email, and phone while preserving ordinary final submission.

- [ ] **Step 1: Expand the `<Form>` slot and set the validation timeout**

Change the opening form tag to:

```vue
<Form
    v-bind="ProfileController.update.form()"
    class="space-y-6"
    :validation-timeout="750"
    v-slot="{ errors, invalid, validate, processing }"
>
```

- [ ] **Step 2: Bind each Profile field to its matching Precognition helpers**

Add the following attributes to the existing controls; retain all current IDs, names, defaults, autocomplete values, placeholders, and classes:

```vue
<Input
    id="name"
    name="name"
    :aria-invalid="invalid('name')"
    @blur="validate('name')"
/>

<Input
    id="email"
    name="email"
    :aria-invalid="invalid('email')"
    @blur="validate('email')"
/>

<Input
    id="phone"
    name="phone"
    :aria-invalid="invalid('phone')"
    @blur="validate('phone')"
/>
```

Do not add handlers to the hidden `temporary_avatar_upload_id`, the `Uploader`, or the verification-email link.

- [ ] **Step 3: Run the full profile feature suite**

Run:

```bash
php artisan test --compact tests/Feature/Profile/ProfileUpdateTest.php
```

Expected: PASS. Existing profile update/avatar coverage remains green and the new Precognition tests retain controller-side-effect protection.

### Task 4: Add General Settings Precognition interactions and invalid control state

**Files:**
- Modify: `resources/js/pages/settings/general/Edit.vue`
- Test: `tests/Feature/Settings/GeneralSettingsAccessTest.php`

**Interfaces:**
- Consumes: `update.form()`, Inertia `<Form>` slot helpers `errors`, `invalid`, `validate`, `processing`, and the existing `Input`, `Textarea`, `Select`, `SelectTrigger`, and `InputError` components.
- Produces: blur-triggered validation and `aria-invalid` state on each General Settings interactive field.

- [ ] **Step 1: Expand the slot contract and configure debounce**

Change the Form tag to:

```vue
<Form
    v-bind="update.form()"
    class="flex flex-col gap-6"
    :validation-timeout="750"
    #default="{ errors, invalid, validate, processing }"
>
```

- [ ] **Step 2: Add validation to the name and description controls**

Add these attributes to the existing controls without replacing their translated placeholders/defaults:

```vue
<Input
    id="site_name"
    name="site_name"
    :aria-invalid="invalid('site_name')"
    @blur="validate('site_name')"
/>

<Textarea
    id="site_description"
    name="site_description"
    :aria-invalid="invalid('site_description')"
    @blur="validate('site_description')"
/>
```

- [ ] **Step 3: Put the locale state and blur handler on the Select trigger**

Keep `name="site_locale"` and `:default-value` on the existing `Select`, then change its trigger to:

```vue
<SelectTrigger
    id="site_locale"
    class="w-full"
    :aria-invalid="invalid('site_locale')"
    @blur="validate('site_locale')"
>
    <SelectValue />
</SelectTrigger>
```

The trigger is the focusable element and already has the shared `aria-invalid` destructive border/ring styles; do not add duplicate classes.

- [ ] **Step 4: Re-run the General Settings feature suite**

Run:

```bash
php artisan test --compact tests/Feature/Settings/GeneralSettingsAccessTest.php
```

Expected: PASS, including the new Precognition tests and the unchanged final-submit settings behavior.

### Task 5: Verify formatting, types, scope, and route middleware

**Files:**
- Verify: `routes/profile.php`
- Verify: `routes/settings.php`
- Verify: `resources/js/pages/Profile.vue`
- Verify: `resources/js/pages/settings/general/Edit.vue`
- Verify: `tests/Feature/Profile/ProfileUpdateTest.php`
- Verify: `tests/Feature/Settings/GeneralSettingsAccessTest.php`

**Interfaces:**
- Consumes: completed routes, form templates, tests, Laravel route list, and project tooling.
- Produces: evidence that security scope, Vue types, formatting, and the production bundle are correct.

- [ ] **Step 1: Run the focused backend suites together**

Run:

```bash
php artisan test --compact tests/Feature/Profile/ProfileUpdateTest.php tests/Feature/Settings/GeneralSettingsAccessTest.php
```

Expected: PASS.

- [ ] **Step 2: Format the touched PHP and frontend files**

Run:

```bash
vendor/bin/pint --dirty --format agent
pnpm exec prettier --write resources/js/pages/Profile.vue resources/js/pages/settings/general/Edit.vue
```

Expected: both commands exit 0. Re-run the focused backend suites if Pint changes PHP files.

- [ ] **Step 3: Type-check, lint, and build the frontend**

Run:

```bash
pnpm run types:check
pnpm run lint:check
pnpm run build
```

Expected: every command exits 0.

- [ ] **Step 4: Confirm the protected route inventory and frontend scope**

Run:

```bash
php artisan route:list --name=profile.update --name=settings.general.update --except-vendor --json
rg -n "validation-timeout|validate\('|aria-invalid|temporary_avatar_upload_id" resources/js/pages/Profile.vue resources/js/pages/settings/general/Edit.vue
git diff --check
```

Expected: exactly the two PATCH routes include `auth` (and Settings' `verified` plus policy middleware) alongside `precognitive` and `throttle:30,1`; the six intended fields have validation/invalid bindings; the avatar identifier has none; and there are no whitespace errors.

- [ ] **Step 5: Review and commit the completed feature**

```bash
git diff -- routes/profile.php routes/settings.php resources/js/pages/Profile.vue resources/js/pages/settings/general/Edit.vue tests/Feature/Profile/ProfileUpdateTest.php tests/Feature/Settings/GeneralSettingsAccessTest.php
git add routes/profile.php routes/settings.php resources/js/pages/Profile.vue resources/js/pages/settings/general/Edit.vue tests/Feature/Profile/ProfileUpdateTest.php tests/Feature/Settings/GeneralSettingsAccessTest.php
git commit -m "feat: add secure precognitive form validation"
```

Expected: the commit contains only the two protected routes, their targeted tests, and the two approved form templates.

## Plan Self-Review

- **Spec coverage:** Tasks 1–2 add and prove route-level Precognition, authorization, throttling, validation responses, and no-side-effect behavior. Tasks 3–4 add each approved blur/invalid-state binding. Task 5 proves all relevant backend and frontend verification.
- **Placeholder scan:** no `TBD`, `TODO`, deferred implementation, or unspecified validation behavior remains.
- **Type consistency:** the slot helpers (`invalid`, `validate`) are supplied by Inertia `<Form>` with Precognition enabled through route middleware; every field name matches its Laravel Form Request rule and `InputError` key.
