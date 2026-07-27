# Form Component Standardization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete the General Settings migration to Inertia `<Form>` and remove native `required` validation from all application form controls.

**Architecture:** Page-level Inertia forms use generated Wayfinder form bindings and `<Form>` slot props for submission state and server errors. Laravel remains the sole validation authority. The passkey-registration component retains its native form because it invokes the passkey package directly rather than making an Inertia visit.

**Tech Stack:** Laravel 13, Inertia v3 Vue, Wayfinder, Vue 3, Tailwind CSS v4, Pest, ESLint, vue-tsc, Vite.

## Global Constraints

- Do not change Form Requests, Fortify rules, controllers, routes, policies, or validation messages.
- Remove only `required`; retain input types, autocomplete, OTP `maxlength`, transforms, reset/error behavior, and non-Inertia passkey handling.
- Use Wayfinder-generated form bindings; do not hard-code URLs.
- In `grid gap-2` field groups, remove redundant `mt-1` control and `mt-2` `InputError` margins.
- Preserve unrelated dirty-worktree changes; stage only files belonging to this refactor after reviewing their diffs.

---

## File Structure

- `resources/js/pages/settings/general/Edit.vue` — repaired settings `<Form>`, defaults, named controls, server error slot, and gap-owned spacing.
- `resources/js/pages/auth/Login.vue` — remove native `required` from email and password.
- `resources/js/pages/auth/ConfirmPassword.vue` — remove native `required` from password.
- `resources/js/pages/auth/TwoFactorChallenge.vue` — remove native `required` from recovery code.
- `resources/js/pages/Profile.vue` — remove native `required` from name/email and redundant field/error margins.

### Task 1: Repair the General Settings Inertia form

**Files:**
- Modify: `resources/js/pages/settings/general/Edit.vue`
- Test: `tests/Feature/Settings/GeneralSettingsAccessTest.php`

**Interfaces:**
- Consumes: `store.form(props.generalSettings)`, `generalSettings`, `localeOptions`, and `<Form>` slot props `errors` and `processing`.
- Produces: a PATCH request containing `site_name`, `site_description`, and `site_locale`; Laravel validation errors are rendered from `errors`.

- [ ] **Step 1: Confirm the current server validation seam passes**

Run: `php artisan test --compact tests/Feature/Settings/GeneralSettingsAccessTest.php`

Expected: PASS, proving the settings endpoint persists valid values and reports validation errors for all three fields.

- [ ] **Step 2: Replace helper-managed controls with native Form data controls**

```vue
<Form
    v-bind="store.form(props.generalSettings)"
    class="flex flex-col gap-6"
    #default="{ errors, processing }"
>
    <Input id="site_name" name="site_name" :default-value="props.generalSettings.site_name" class="block w-full" />
    <InputError :message="errors.site_name" />

    <Textarea id="site_description" name="site_description" :default-value="props.generalSettings.site_description" class="block w-full" />
    <InputError :message="errors.site_description" />

    <Select name="site_locale" :default-value="props.generalSettings.site_locale">
        <!-- retain the existing trigger, content, and locale option loop -->
    </Select>
    <InputError :message="errors.site_locale" />

    <Button :disabled="processing" data-test="update-general-settings-button">Save</Button>
</Form>
```

Retain the existing labels, card structure, locale option loop, translations, and button test selector. Remove all `v-model="form.*"`, `form.errors.*`, `form.processing`, `required`, `mt-1`, and `mt-2` uses. Close the component with `</Form>`, not `</form>`.

- [ ] **Step 3: Re-run server validation coverage**

Run: `php artisan test --compact tests/Feature/Settings/GeneralSettingsAccessTest.php`

Expected: PASS; the frontend refactor does not change the endpoint contract.

### Task 2: Remove native required validation from application Inertia forms

**Files:**
- Modify: `resources/js/pages/auth/Login.vue`
- Modify: `resources/js/pages/auth/ConfirmPassword.vue`
- Modify: `resources/js/pages/auth/TwoFactorChallenge.vue`
- Modify: `resources/js/pages/Profile.vue`
- Test: `tests/Feature/Auth/AuthenticationTest.php`
- Test: `tests/Feature/Auth/PasswordConfirmationTest.php`
- Test: `tests/Feature/Auth/TwoFactorChallengeTest.php`
- Test: `tests/Feature/Profile/ProfileUpdateTest.php`

**Interfaces:**
- Consumes: existing generated `<Form>` bindings and Laravel/Fortify validation responses.
- Produces: unchanged request field names and server-rendered Inertia error behavior without browser `required` blocking.

- [ ] **Step 1: Remove only the seven native required attributes**

```text
resources/js/pages/auth/Login.vue              email, password
resources/js/pages/auth/ConfirmPassword.vue    password
resources/js/pages/auth/TwoFactorChallenge.vue recovery_code
resources/js/pages/Profile.vue                 name, email
resources/js/pages/settings/general/Edit.vue   site_name (Task 1)
```

Keep the OTP component's `:maxlength="6"`, password reset behavior, and two-factor mode-switch `clearErrors()` behavior.

- [ ] **Step 2: Normalize Profile field spacing**

```vue
<Input id="name" class="block w-full" name="name" :default-value="user.name" />
<InputError :message="errors.name" />
```

Apply the same removal of `mt-1` and `mt-2` classes to email and phone while preserving their semantic attributes and placeholders.

- [ ] **Step 3: Run the focused behavior tests**

```bash
php artisan test --compact tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/PasswordConfirmationTest.php tests/Feature/Auth/TwoFactorChallengeTest.php tests/Feature/Profile/ProfileUpdateTest.php
```

Expected: PASS; login, password confirmation, two-factor challenge, and profile validation continue to be enforced by Laravel.

### Task 3: Verify the frontend migration and scope

**Files:**
- Verify: `resources/js/pages/settings/general/Edit.vue`
- Verify: `resources/js/pages/auth/Login.vue`
- Verify: `resources/js/pages/auth/ConfirmPassword.vue`
- Verify: `resources/js/pages/auth/TwoFactorChallenge.vue`
- Verify: `resources/js/pages/Profile.vue`

**Interfaces:**
- Consumes: completed form templates and existing TypeScript/Vue tooling.
- Produces: evidence that Form slot types resolve, no targeted native required validation remains, and the frontend bundles.

- [ ] **Step 1: Confirm the migration inventory**

```bash
rg -n '\\buseForm\\b|\\brequired\\b' resources/js --glob '*.{vue,ts}'
rg -n '</form>|v-model="form\\.|form\\.(errors|processing)' resources/js/pages/settings/general/Edit.vue
```

Expected: no application `useForm` or `required` results, and no legacy helper bindings or closing native form tag in General Settings. The passkey component may continue to contain `<form>` because its `usePasskeyRegister()` workflow is not an Inertia visit.

- [ ] **Step 2: Run frontend static checks and build**

```bash
pnpm run format:check
pnpm run types:check
pnpm run lint:check
pnpm run build
```

Expected: every command exits 0.

- [ ] **Step 3: Review scope and commit the refactor**

```bash
git diff --check
git diff -- resources/js/pages/settings/general/Edit.vue resources/js/pages/auth/Login.vue resources/js/pages/auth/ConfirmPassword.vue resources/js/pages/auth/TwoFactorChallenge.vue resources/js/pages/Profile.vue
git add resources/js/pages/settings/general/Edit.vue resources/js/pages/auth/Login.vue resources/js/pages/auth/ConfirmPassword.vue resources/js/pages/auth/TwoFactorChallenge.vue resources/js/pages/Profile.vue
git commit -m "refactor: standardize Inertia forms"
```

Expected: no whitespace errors and a commit containing only the intended form migration. If any target file includes pre-existing unrelated edits, separate them before committing rather than discarding them.

## Plan Self-Review

- **Spec coverage:** Task 1 repairs General Settings; Task 2 removes every discovered `required` attribute and normalizes the affected spacing; Task 3 proves there are no remaining helper/native-validation regressions and validates the frontend build.
- **Placeholder scan:** no implementation placeholders remain.
- **Type consistency:** all slot names (`errors`, `processing`) are Inertia `<Form>` slot props, and request names match the Laravel endpoint field names.
