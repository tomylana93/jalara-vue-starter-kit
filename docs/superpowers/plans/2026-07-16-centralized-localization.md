# Centralized Localization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use
> `superpowers:subagent-driven-development` (recommended) or
> `superpowers:executing-plans` to implement this plan task-by-task. Steps use
> checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete and enforce one Laravel-authored localization contract for
all application-owned backend and Vue presentation copy, except
`resources/js/components/ui/**`.

**Architecture:** `lang/{locale}/*.php` remains the human-edited source.
`FrontendLocaleExporter` validates complete `en`/`id` catalogs before
atomically generating ignored JSON and a tracked TypeScript key union. Laravel
translates server-owned messages, while a typed deep frontend module translates
Vue-owned copy through `trans`, `transChoice`, and a safe rich-text adapter.

**Tech Stack:** PHP 8.5, Laravel 13 localization, Inertia 3, Vue 3.5,
TypeScript 5.2, Node 24 built-in test runner, ESLint 10 with eslint-plugin-vue,
Pest 4 browser tests, Vite 8.

## Global Constraints

- Locale remains global and is selected only by
  `GeneralSettings.site_locale`.
- Human-authored translations live only in `lang/en/*.php` and
  `lang/id/*.php`.
- Use semantic feature keys; English sentences must not be translation keys.
- Every changed key must include `en` and `id` values in the same commit.
- Backend-owned messages are translated by Laravel; Vue-owned copy is
  translated in Vue.
- Internal exceptions, logs, and Artisan output remain English and are not
  localization targets.
- Do not modify `resources/js/components/ui/**`.
- Do not add `vue-i18n` or any other dependency.
- Keep `lang/en.json` and `lang/id.json` ignored; track the generated
  TypeScript key contract.
- Do not use `v-html` for translated content.
- Each task starts from the declared fixed point, follows red-green-refactor,
  receives independent review, and remains deployable.
- The integrator alone owns generated locale artifacts, shared translation
  types, `vite.config.ts`, `eslint.config.js`, and dependency manifests.
- Do not commit, push, or open a PR unless the developer explicitly requests
  it.

---

## File and Interface Map

### Localization infrastructure

- `app/Support/Localization/FrontendLocaleExporter.php` validates catalogs and
  atomically writes JSON plus the key contract.
- `app/Support/Localization/LocalizationCatalog.php` is an internal immutable
  catalog that flattens keys and exposes validation data.
- `app/Console/Commands/LangExportCommand.php` exposes output overrides for
  tests while keeping current defaults.
- `resources/js/types/translation.generated.ts` is the deterministic tracked
  `TranslationKey` union.
- `resources/js/lib/translation.ts` owns pure lookup, replacement,
  plural-choice, and rich-slot tokenization logic.
- `resources/js/composables/useTrans.ts` binds that pure interface to the
  Inertia locale prop.
- `resources/js/components/TranslatedText.vue` renders named rich-text slots
  without HTML injection.

### Catalogs

- Existing `auth.php`, `general.php`, and `settings.php` catalogs are retained
  and normalized.
- Create `profile.php`, `security.php`, and `navigation.php` per locale.
- Create/publish `validation.php` and `passwords.php` per locale for
  user-visible Laravel/Fortify messages.

### Enforcement and verification

- `eslint-local-rules/no-untranslated-copy.js` implements the Vue-template
  hardcoded-copy rule without a new package.
- `eslint.config.js` enables the rule outside the exact UI exclusion.
- `tests/Feature/Localization/PresentationCopyArchitectureTest.php` audits
  backend presentation literals and forbidden sentence keys.
- `tests/Browser/LocalizationSmokeTest.php` verifies representative `en` and
  `id` flows in a real browser.

---

### Task 1: Make catalog export strict, atomic, and typed

**Files:**

- Create: `app/Support/Localization/LocalizationCatalog.php`
- Create: `resources/js/types/translation.generated.ts`
- Modify: `app/Support/Localization/FrontendLocaleExporter.php`
- Modify: `app/Console/Commands/LangExportCommand.php`
- Modify: `vite.config.ts`
- Test: `tests/Feature/Localization/FrontendLocaleExporterTest.php`

**Interfaces:**

- Produces:
  `LocalizationCatalog::fromDirectory(Filesystem $files, string $source,
  list<string> $locales, string $canonicalLocale = 'en'): self`.
- Produces: `LocalizationCatalog::messages(string $locale): array` and
  `LocalizationCatalog::keys(): list<string>`.
- Produces:
  `FrontendLocaleExporter::export(?array $locales = null,
  ?string $outputDirectory = null, ?string $sourceDirectory = null,
  ?string $typesPath = null): array<string, string>`.
- Produces: tracked `export type TranslationKey = 'auth.failed' | ...;`.

- [ ] **Step 1: Extend exporter tests with failing contract cases**

Add explicit tests using temporary catalogs:

```php
it('rejects missing and extra keys with locale and key context', function (): void {
    (new Filesystem)->put("{$this->sourceDir}/id/general.php", "<?php\nreturn ['action' => ['cancel' => 'Batal']];\n");

    expect(fn () => app(FrontendLocaleExporter::class)->export(
        ['en', 'id'],
        $this->outputDir,
        $this->sourceDir,
        "{$this->outputDir}/translation.generated.ts",
    ))->toThrow(RuntimeException::class, 'Locale [id]');
});

it('rejects non-string leaves and placeholder mismatches', function (): void {
    (new Filesystem)->put("{$this->sourceDir}/en/general.php", "<?php\nreturn ['welcome' => 'Hello :name'];\n");
    (new Filesystem)->put("{$this->sourceDir}/id/general.php", "<?php\nreturn ['welcome' => 'Halo :user'];\n");

    expect(fn () => app(FrontendLocaleExporter::class)->export(
        ['en', 'id'],
        $this->outputDir,
        $this->sourceDir,
        "{$this->outputDir}/translation.generated.ts",
    ))->toThrow(RuntimeException::class, 'placeholder');
});
```

Also cover duplicate flattened paths, invalid plural intervals, stable sorted
output, and failure leaving pre-existing JSON/type files byte-for-byte intact.

- [ ] **Step 2: Run the focused exporter tests and confirm red**

Run:

```bash
php artisan test --compact tests/Feature/Localization/FrontendLocaleExporterTest.php
```

Expected: FAIL because parity, placeholder, plural, type-output, and atomicity
behavior do not exist.

- [ ] **Step 3: Implement the internal catalog and strict validation**

Implement `LocalizationCatalog` with these invariants:

```php
final readonly class LocalizationCatalog
{
    /**
     * @param array<string, array<string, mixed>> $messages
     * @param list<string> $keys
     */
    private function __construct(
        private array $messages,
        private array $keys,
    ) {}

    public static function fromDirectory(
        Filesystem $files,
        string $source,
        array $locales,
        string $canonicalLocale = 'en',
    ): self;

    /** @return array<string, mixed> */
    public function messages(string $locale): array;

    /** @return list<string> */
    public function keys(): array;
}
```

Flatten nested arrays into sorted dot paths. Compare every selected locale to
canonical `en`, require string leaves, extract placeholders with
`/:([A-Za-z_][A-Za-z0-9_]*)/`, and validate plural selectors limited to
Laravel forms `{n}`, `[n,m]`, `[n,*]`, and `[* ,n]` after removing whitespace.
Throw `RuntimeException` containing the locale and exact key.

- [ ] **Step 4: Generate all outputs in temporary files before replacement**

Refactor `FrontendLocaleExporter` so it constructs and validates the complete
catalog first and writes sibling temporary files. Before replacement, copy all
existing targets to temporary backups; replace targets with
`Filesystem::move()`, and on any replacement error restore every backup before
rethrowing. Delete backups only after the complete set is installed. Generate
the type file deterministically:

```ts
// This file is generated by `php artisan lang:export`. Do not edit.
export type TranslationKey =
    | 'auth.failed'
    | 'auth.password'
    | 'auth.throttle';
```

Default `$typesPath` to
`resource_path('js/types/translation.generated.ts')`. Add
`--types-path=` to `LangExportCommand` for isolated tests. Preserve the
existing return value as the locale-to-JSON-path map.

- [ ] **Step 5: Make Vite watch every human-authored catalog**

Keep Vite invoking `php artisan lang:export`, but restrict the watcher to
`lang/{en,id}/**/*.php` and reload only after success. Do not watch generated
JSON or the generated TypeScript file.

- [ ] **Step 6: Run exporter tests and generation**

Run:

```bash
php artisan test --compact tests/Feature/Localization/FrontendLocaleExporterTest.php
php artisan lang:export --no-interaction
git diff --check
```

Expected: tests PASS; ignored JSON is regenerated; the tracked type file is
sorted and stable on a second export.

- [ ] **Step 7: Independent review checkpoint**

Reviewer verifies atomic replacement, exact parity diagnostics, deterministic
sorting, no writes outside declared paths, and no edits under
`resources/js/components/ui/**`. Resolve findings before Task 2.

### Task 2: Deepen the typed Vue localization module

**Files:**

- Create: `resources/js/lib/translation.ts`
- Create: `resources/js/components/TranslatedText.vue`
- Create: `tests/Frontend/translation.test.ts`
- Modify: `resources/js/composables/useTrans.ts`
- Modify: `package.json`

**Interfaces:**

- Consumes: `TranslationKey` from
  `resources/js/types/translation.generated.ts`.
- Produces: `trans`, `transChoice`, `translationParts`, `useTrans`, and the
  `TranslatedText` named-slot adapter.

- [ ] **Step 1: Write pure Node tests for lookup and formatting**

Use `node:test` and `node:assert/strict`, injecting a small message map rather
than importing Vite glob state:

```ts
test('falls back to English and applies Laravel capitalization', () => {
    const translator = createTranslator({
        en: { greeting: { welcome: 'Hello :name, :NAME' } },
        id: {},
    });

    assert.equal(
        translator.trans('greeting.welcome', { name: 'Ayu' }, 'id'),
        'Hello Ayu, AYU',
    );
});

test('selects explicit and interval plural branches', () => {
    const translator = createTranslator({
        en: { files: '{0} No files|{1} One file|[2,*] :count files' },
    });

    assert.equal(translator.transChoice('files', 0), 'No files');
    assert.equal(translator.transChoice('files', 3), '3 files');
});
```

Cover raw-key fallback, `:key`/`:Key`/`:KEY`, overlapping replacement names,
two-branch singular/plural strings, explicit selectors, intervals, and rich
slot tokenization that never interprets HTML.

- [ ] **Step 2: Add and run the Node test command**

Add:

```json
"test:frontend": "node --experimental-strip-types --test tests/Frontend/**/*.test.ts"
```

Run `pnpm run test:frontend` and expect FAIL because `createTranslator` and
the other pure functions do not exist.

- [ ] **Step 3: Extract the pure translation implementation**

Implement a factory with injected messages:

```ts
export function createTranslator(messages: TranslationCatalog): Translator {
    return {
        trans: (key, replacements = {}, locale = 'en') =>
            replacePlaceholders(
                getMessage(messages, locale, key) ?? key,
                replacements,
            ),
        transChoice: (key, count, replacements = {}, locale = 'en') =>
            replacePlaceholders(
                choose(getMessage(messages, locale, key) ?? key, count),
                { ...replacements, count },
            ),
    };
}
```

Keep parsing and selector helpers private. Export only the types and functions
required by callers/tests. Do not use `eval`, DOM parsing, or HTML rendering.

- [ ] **Step 4: Bind the pure translator to Inertia locale state**

Keep the eager Vite glob in `useTrans.ts`, instantiate one translator, and
return locale-bound typed closures:

```ts
return {
    locale,
    trans: (key, replacements = {}, target = locale.value) =>
        translator.trans(key, replacements, target),
    transChoice: (key, count, replacements = {}, target = locale.value) =>
        translator.transChoice(key, count, replacements, target),
};
```

Both `trans` functions must accept only `TranslationKey`, while internal test
factories may use a generic string key type.

- [ ] **Step 5: Implement safe named-slot rich text**

`TranslatedText.vue` accepts a typed `translationKey`, replacements, and a
list of allowed slot names. `translationParts()` splits only declared
`:slotName` tokens. The template renders text normally and named slots through
Vue:

```vue
<template>
    <template v-for="(part, index) in parts" :key="index">
        <slot v-if="part.slot" :name="part.slot" />
        <template v-else>{{ part.text }}</template>
    </template>
</template>
```

Unknown tokens remain escaped text; never use `v-html`.

- [ ] **Step 6: Verify the frontend module**

Run:

```bash
pnpm run test:frontend
pnpm run types:check
pnpm run lint:check
```

Expected: all PASS.

- [ ] **Step 7: Independent review checkpoint**

Reviewer compares plural results with Laravel examples for zero, one, two,
and interval ranges; verifies type narrowing and the absence of HTML injection.

### Task 3: Localize backend and framework-owned user messages

**Files:**

- Create: `lang/en/profile.php`
- Create: `lang/id/profile.php`
- Create: `lang/en/security.php`
- Create: `lang/id/security.php`
- Create: `lang/en/validation.php`
- Create: `lang/id/validation.php`
- Create: `lang/en/passwords.php`
- Create: `lang/id/passwords.php`
- Modify: `lang/en/auth.php`
- Modify: `lang/id/auth.php`
- Modify: `lang/en/general.php`
- Modify: `lang/id/general.php`
- Modify: `app/Enums/SiteLocale.php`
- Modify: `app/Http/Controllers/Profile/ProfileController.php`
- Modify: `app/Http/Controllers/Security/SecurityController.php`
- Modify: `app/Actions/PromoteTemporaryAvatarUpload.php`
- Test: `tests/Feature/Localization/BackendPresentationMessagesTest.php`
- Test: existing auth, profile, security, and general-settings feature tests

**Interfaces:**

- Consumes Laravel `__()` and `trans_choice()` using semantic keys.
- Produces `SiteLocale::options(): list<array{value: string, label: string}>`
  with translated endonyms.

- [ ] **Step 1: Write failing backend locale tests**

Cover both locales with a dataset:

```php
it('translates backend presentation messages', function (string $locale, array $expected): void {
    app()->setLocale($locale);

    expect(__('profile.toast.updated'))->toBe($expected['profile_updated'])
        ->and(__('security.password.toast.updated'))->toBe($expected['password_updated'])
        ->and(SiteLocale::options())->toContain($expected['indonesian_option']);
})->with([
    'English' => ['en', [
        'profile_updated' => 'Profile updated.',
        'password_updated' => 'Password updated.',
        'indonesian_option' => ['value' => 'id', 'label' => 'Bahasa Indonesia'],
    ]],
    'Indonesian' => ['id', [
        'profile_updated' => 'Profil diperbarui.',
        'password_updated' => 'Kata sandi diperbarui.',
        'indonesian_option' => ['value' => 'id', 'label' => 'Bahasa Indonesia'],
    ]],
]);
```

Add request-level assertions for validation, invalid staged avatar, login
failure/throttling, password reset, email verification, and backend flash
messages.

- [ ] **Step 2: Run focused backend tests and confirm red**

Run:

```bash
php artisan test --compact tests/Feature/Localization/BackendPresentationMessagesTest.php
```

Expected: FAIL on absent semantic keys and hardcoded locale labels.

- [ ] **Step 3: Add complete framework and feature catalogs**

Use Laravel 13's published English validation/password files as the key shape.
Add matching Indonesian values. Add these semantic application keys in both
locales:

```text
profile.error.temporary_avatar_unavailable
profile.error.temporary_avatar_invalid
profile.toast.updated
profile.toast.avatar_removed
security.password.toast.updated
general.locale.en
general.locale.id
```

English locale labels are `English` and `Bahasa Indonesia`; Indonesian locale
labels remain the same endonyms. Preserve placeholder names exactly across
locales.

- [ ] **Step 4: Replace sentence keys and hardcoded labels**

Replace backend calls exactly by ownership:

```php
__('Profile updated.')
// becomes
__('profile.toast.updated')

__('Password updated.')
// becomes
__('security.password.toast.updated')
```

Use the two `profile.error.*` keys for staged-avatar validation. Change
`SiteLocale::options()` to resolve `general.locale.en` and
`general.locale.id`; do not cache translated labels statically.

- [ ] **Step 5: Verify references and backend behavior**

Use Serena reference search for every removed sentence key, then run:

```bash
php artisan lang:export --no-interaction
php artisan test --compact tests/Feature/Localization/BackendPresentationMessagesTest.php
php artisan test --compact tests/Feature/Auth tests/Feature/Profile tests/Feature/Security tests/Feature/Settings
```

Expected: no old sentence-key references; all focused tests PASS.

- [ ] **Step 6: Independent review checkpoint**

Reviewer confirms that only presentation messages were localized and internal
exceptions/logs/CLI strings remain unchanged.

### Task 4: Migrate application shell, navigation, dashboard, and shared copy

**Files:**

- Create: `lang/en/navigation.php`
- Create: `lang/id/navigation.php`
- Modify: `lang/en/general.php`
- Modify: `lang/id/general.php`
- Modify: `resources/js/components/AppHeader.vue`
- Modify: `resources/js/components/AppSidebar.vue`
- Modify: `resources/js/components/NavMain.vue`
- Modify: `resources/js/components/UserMenuContent.vue`
- Modify: `resources/js/components/AppearanceToggle.vue`
- Modify: `resources/js/components/PasswordInput.vue`
- Modify: `resources/js/pages/Dashboard.vue`
- Modify: applicable non-UI app layouts and shared components found by audit
- Test: `tests/Feature/Localization/ShellLocalizationTest.php`

**Interfaces:**

- Consumes typed `useTrans().trans`.
- Produces navigation/general keys used by the shell.

- [ ] **Step 1: Add a failing shell-copy inventory test**

Assert catalog values and source migration for these exact meanings:

```text
navigation.menu.label        Navigation menu / Menu navigasi
navigation.platform.label    Platform / Platform
navigation.profile           Profile / Profil
navigation.logout            Log out / Keluar
navigation.dashboard         Dashboard / Dasbor
general.appearance.change    Change appearance / Ubah tampilan
general.password.show        Show password / Tampilkan kata sandi
general.password.hide        Hide password / Sembunyikan kata sandi
```

The test must assert the old literals no longer occur in the owned Vue files.

- [ ] **Step 2: Run the shell test and confirm red**

Run `php artisan test --compact tests/Feature/Localization/ShellLocalizationTest.php`.
Expected: FAIL because literals still exist.

- [ ] **Step 3: Add keys and migrate owned callers**

Call `useTrans()` once per component and bind text/props with typed keys.
Translate `<Head title>` values and accessible-only text as well as visible
copy. Do not touch any file under `components/ui`.

- [ ] **Step 4: Regenerate and verify**

Run:

```bash
php artisan lang:export --no-interaction
php artisan test --compact tests/Feature/Localization/ShellLocalizationTest.php
pnpm run types:check
pnpm run lint:check
```

Expected: all PASS.

- [ ] **Step 5: Independent review checkpoint**

Reviewer checks navigation semantics, accessibility labels, and the exact UI
exclusion.

### Task 5: Migrate all authentication Vue copy

**Files:**

- Modify: `lang/en/auth.php`
- Modify: `lang/id/auth.php`
- Modify: `resources/js/pages/auth/Login.vue`
- Modify: `resources/js/pages/auth/ForgotPassword.vue`
- Modify: `resources/js/pages/auth/ResetPassword.vue`
- Modify: `resources/js/pages/auth/ConfirmPassword.vue`
- Modify: `resources/js/pages/auth/VerifyEmail.vue`
- Modify: `resources/js/pages/auth/TwoFactorChallenge.vue`
- Modify: `resources/js/layouts/auth/AuthCardLayout.vue`
- Modify: `resources/js/layouts/auth/AuthSimpleLayout.vue`
- Modify: `resources/js/layouts/auth/AuthSplitLayout.vue`
- Test: `tests/Feature/Localization/AuthFrontendLocalizationTest.php`

**Interfaces:**

- Consumes `trans` and `TranslatedText`.
- Produces complete `auth.*` Vue catalog coverage.

- [ ] **Step 1: Write the failing auth copy test**

Test both locales for login, forgot/reset/confirm password, verification, and
two-factor challenge keys. Include titles, card copy, labels, placeholders,
actions, recovery-code prompts, OTP accessibility labels, passkey labels, and
loading labels. Assert the owned Vue files contain `trans(` or
`<TranslatedText` and no corresponding English literals.

- [ ] **Step 2: Run the auth localization test and confirm red**

Run:

```bash
php artisan test --compact tests/Feature/Localization/AuthFrontendLocalizationTest.php
```

Expected: FAIL on the still-hardcoded auth pages.

- [ ] **Step 3: Complete the auth catalogs**

Extend existing semantic branches rather than adding sentence keys:

```text
auth.verify_email.*
auth.two_factor_challenge.*
auth.confirm_password.passkey.*
auth.common.action.continue
auth.common.action.logout
auth.common.aria.otp
auth.common.aria.recovery_code
```

Use `TranslatedText` for verification/resend sentences containing links. Keep
example email values as translated placeholder entries, not lint suppressions.

- [ ] **Step 4: Migrate every auth caller**

Bind `<Head :title>`, layout props, labels, placeholders, buttons, loading
copy, accessible labels, and prompts. Preserve Fortify route behavior and form
field names exactly.

- [ ] **Step 5: Verify auth behavior**

Run:

```bash
php artisan lang:export --no-interaction
php artisan test --compact tests/Feature/Localization/AuthFrontendLocalizationTest.php
php artisan test --compact tests/Feature/Auth
pnpm run types:check
pnpm run lint:check
```

Expected: all PASS.

- [ ] **Step 6: Independent review checkpoint**

Reviewer verifies no auth flow, route, form field, or security behavior changed
and that all accessibility strings are localized.

### Task 6: Migrate profile and uploader presentation copy

**Files:**

- Modify: `lang/en/profile.php`
- Modify: `lang/id/profile.php`
- Modify: `lang/en/general.php`
- Modify: `lang/id/general.php`
- Modify: `resources/js/pages/Profile.vue`
- Modify: `resources/js/components/uploader/Uploader.vue`
- Test: `tests/Feature/Localization/ProfileFrontendLocalizationTest.php`
- Test: existing profile and upload feature tests

**Interfaces:**

- Consumes `trans` and `TranslatedText`.
- Produces complete `profile.*` and `general.uploader.*` copy.

- [ ] **Step 1: Write failing profile copy tests**

Cover `en` and `id` values for profile information heading/description,
avatar, remove avatar, name/email/phone labels and placeholders, unverified
email, resend verification link/result, save action, and FilePond-facing
uploader messages.

- [ ] **Step 2: Run focused tests and confirm red**

Run `php artisan test --compact tests/Feature/Localization/ProfileFrontendLocalizationTest.php`.
Expected: FAIL on hardcoded `Profile.vue` copy.

- [ ] **Step 3: Add semantic keys and migrate callers**

Use these branches:

```text
profile.information.*
profile.avatar.*
profile.form.label.*
profile.form.placeholder.*
profile.email_verification.*
profile.action.save
general.uploader.*
```

Use `TranslatedText` for resend-link sentences. Pass resolved strings into
FilePond props; do not modify the third-party adapter's behavior or endpoint
resolvers.

- [ ] **Step 4: Verify profile and uploader behavior**

Run:

```bash
php artisan lang:export --no-interaction
php artisan test --compact tests/Feature/Localization/ProfileFrontendLocalizationTest.php
php artisan test --compact tests/Feature/Profile tests/Feature/Actions/Profile
pnpm run types:check
```

Expected: all PASS, including `AvatarUploadTest` and the staged/removal action
tests.

- [ ] **Step 5: Independent review checkpoint**

Reviewer checks email-verification grammar in both locales, upload error
mapping, and unchanged upload lifecycle behavior.

### Task 7: Migrate security, two-factor, and passkey copy

**Files:**

- Modify: `lang/en/security.php`
- Modify: `lang/id/security.php`
- Modify: `resources/js/pages/Security.vue`
- Modify: `resources/js/components/ManageTwoFactor.vue`
- Modify: `resources/js/components/TwoFactorSetupModal.vue`
- Modify: `resources/js/components/TwoFactorRecoveryCodes.vue`
- Modify: `resources/js/components/ManagePasskeys.vue`
- Modify: `resources/js/components/PasskeyRegister.vue`
- Modify: `resources/js/components/PasskeyItem.vue`
- Modify: `resources/js/components/PasskeyVerify.vue`
- Test: `tests/Feature/Localization/SecurityFrontendLocalizationTest.php`
- Test: existing security, two-factor, and passkey tests

**Interfaces:**

- Consumes `trans`, `transChoice`, and `TranslatedText`.
- Produces complete `security.password.*`, `security.two_factor.*`, and
  `security.passkeys.*` catalog branches.

- [ ] **Step 1: Write failing security copy tests**

Inventory and test all visible/accessibility copy: password form labels and
actions; 2FA status, explanation, setup, OTP/recovery prompts, back/confirm,
recovery-code instructions and regeneration; passkey unsupported/empty states,
registration labels/help/placeholders, removal dialog, loading labels, and
item-count text through `transChoice`.

- [ ] **Step 2: Run focused tests and confirm red**

Run:

```bash
php artisan test --compact tests/Feature/Localization/SecurityFrontendLocalizationTest.php
```

Expected: FAIL on hardcoded security components.

- [ ] **Step 3: Add complete security catalogs**

Use the declared feature branches. For counts, use Laravel syntax in both
locales, for example:

```php
'count' => '{0} No passkeys|{1} One passkey|[2,*] :count passkeys',
```

and an Indonesian message with identical `:count` placeholder and selectors.
Use named-slot translations for recovery-code instructions containing bold or
interactive text.

- [ ] **Step 4: Migrate security callers without changing state flows**

Only replace presentation literals. Keep composable calls, WebAuthn payloads,
Fortify endpoints, form field names, and modal state transitions unchanged.

- [ ] **Step 5: Verify security behavior**

Run:

```bash
php artisan lang:export --no-interaction
php artisan test --compact tests/Feature/Localization/SecurityFrontendLocalizationTest.php
php artisan test --compact tests/Feature/Security tests/Feature/Auth/TwoFactorChallengeTest.php
pnpm run test:frontend
pnpm run types:check
```

Expected: localization tests and every existing applicable security test PASS.

- [ ] **Step 6: Independent security review checkpoint**

Reviewer must be different from the writer and verify that the diff changes
copy only, introduces no auth/security behavior change, and never sends secret
or recovery-code content into translation strings.

### Task 8: Finish settings/remaining copy and enforce hardcoded-copy gates

**Files:**

- Modify: `lang/en/settings.php`
- Modify: `lang/id/settings.php`
- Modify: `resources/js/pages/settings/Index.vue`
- Modify: `resources/js/pages/settings/general/Edit.vue`
- Modify: `resources/js/layouts/settings/Layout.vue`
- Create: `eslint-local-rules/no-untranslated-copy.js`
- Create: `tests/Frontend/no-untranslated-copy.test.ts`
- Modify: `eslint.config.js`
- Create: `tests/Feature/Localization/PresentationCopyArchitectureTest.php`
- Test: existing settings localization and access tests

**Interfaces:**

- Consumes the typed frontend localization interface.
- Produces ESLint rule
  `local/no-untranslated-copy` and the backend presentation architecture gate.

- [ ] **Step 1: Write rule tests as executable ESLint fixtures**

In `tests/Frontend/no-untranslated-copy.test.ts`, use ESLint's `Linter` API
with the Vue parser already configured by the repository to prove the rule
reports:

```vue
<Button title="Save">Save</Button>
<Input placeholder="Full name" aria-label="Full name" />
```

and accepts:

```vue
<Button :title="trans('general.action.save')">
    {{ trans('general.action.save') }}
</Button>
```

Also prove files below `resources/js/components/ui/**` are excluded and a
single-node `localization-ignore` suppression is honored.

- [ ] **Step 2: Write the failing backend presentation architecture test**

Audit controllers, form requests, Fortify actions/responses, notifications,
and mailables for sentence literals passed to flash, validation, or
presentation response structures. Explicitly reject `__('English sentence')`.
Exclude exception constructors, logger calls, and `app/Console/**`.

- [ ] **Step 3: Run lint/tests and confirm red**

Run:

```bash
pnpm run lint:check
php artisan test --compact tests/Feature/Localization/PresentationCopyArchitectureTest.php
```

Expected: FAIL with a concrete remaining-file inventory.

- [ ] **Step 4: Implement and enable the local ESLint rule**

Register a local plugin object in `eslint.config.js` and enable its rule for
`resources/js/**/*.{vue,ts}`. Inspect Vue template `VText` nodes and static
values for `title`, `description`, `label`, `placeholder`, and `aria-label`.
Ignore whitespace, punctuation-only content, URLs, route names, field names,
CSS classes, and explicitly suppressed nodes. Change the current UI ignore
from `resources/js/components/ui/*` to
`resources/js/components/ui/**` so nested files are unambiguously excluded.

- [ ] **Step 5: Clear every reported settings literal**

Migrate the three owned settings files to semantic keys in both locales.
Narrow suppressions are allowed only for immutable product names or literal
examples whose display must not vary; each suppression comment must state why.
If the newly enabled rule reports an application-owned file outside Tasks 4-8,
stop and amend this plan's ownership map before editing that file.

- [ ] **Step 6: Delete obsolete keys after reference verification**

Use Serena reference search for each candidate. Remove only keys with zero
PHP and TypeScript/Vue references. Regenerate artifacts after deletion.

- [ ] **Step 7: Verify final static enforcement**

Run:

```bash
php artisan lang:export --no-interaction
pnpm run lint:check
pnpm run format:check
pnpm run types:check
pnpm run test:frontend
php artisan test --compact tests/Feature/Localization
php artisan test --compact tests/Feature/Settings
```

Expected: all PASS and a second `lang:export` produces no tracked diff.

- [ ] **Step 8: Independent review checkpoint**

Reviewer checks false-positive suppressions, ensures no broad exclusions were
added, confirms every remaining literal is non-presentation data, and verifies
that `components/ui/**` stayed unchanged.

### Task 9: Browser smoke, full gate, final review, and Serena memory

**Files:**

- Create: `tests/Browser/LocalizationSmokeTest.php`
- Modify: localization files only if browser evidence exposes a missing or
  incorrect key
- Update after all gates: the appropriate Serena localization memory

**Interfaces:**

- Consumes all completed localization slices.
- Produces final two-locale runtime evidence and durable Serena project
  knowledge.

- [ ] **Step 1: Write browser smoke coverage for both global locales**

Use a dataset that updates `GeneralSettings.site_locale` before each request.
Authenticate a suitable factory user for protected routes. Visit login,
profile, security, and general settings and assert representative copy plus
runtime health:

```php
it('renders primary flows in the configured locale', function (string $locale, array $copy): void {
    $settings = app(GeneralSettings::class);
    $settings->site_locale = $locale;
    $settings->save();

    $page = visit('/login');

    $page->assertSee($copy['login'])
        ->assertDontSee('auth.login.action.submit')
        ->assertNoSmoke();
})->with([
    'English' => ['en', ['login' => 'Log in']],
    'Indonesian' => ['id', ['login' => 'Masuk']],
]);
```

Add authenticated visits for profile, security, and settings. Assert no raw
key prefixes (`auth.`, `profile.`, `security.`, `settings.`, `navigation.`), no
empty primary controls, no console logs, and no JavaScript errors.

- [ ] **Step 2: Run browser tests and resolve evidence-backed gaps**

Run:

```bash
php artisan test --compact tests/Browser/LocalizationSmokeTest.php
```

Expected: PASS in the installed default browser. Fix only localization gaps
demonstrated by the failure; use systematic debugging for runtime/test issues.

- [ ] **Step 3: Run the canonical finishing gate in repository order**

Routes/controllers are not expected to change route contracts, but if they did,
run Wayfinder generation first. Then run:

```bash
vendor/bin/pint --dirty --format agent
pnpm run lint
pnpm run format
pnpm run test:frontend
php artisan test --compact tests/Feature/Localization tests/Browser/LocalizationSmokeTest.php
composer run agent:gate
```

Expected: every command exits 0. Run `git status --short` and confirm ignored
JSON is absent and no file under `resources/js/components/ui/**` changed.

- [ ] **Step 4: Obtain independent fixed-diff review**

The reviewer reports Standards and Spec findings against
`docs/superpowers/specs/2026-07-16-centralized-localization-design.md`.
Resolve P0-P2 findings, explicitly disposition P3 findings, rerun the smallest
affected tests, then rerun `composer run agent:gate`.

- [ ] **Step 5: Review final diff against the fixed point**

Confirm acceptance criteria, catalog parity, no sentence keys, no unrelated
cleanup, no dependency/schema/public-route change, generated artifact
stability, and exact UI-folder exclusion.

- [ ] **Step 6: Create or update the Serena localization memory**

Only after implementation, independent review, browser smoke, and the full
gate pass, read existing memories to avoid duplication. Create or update one
durable memory (prefer `localization/core` unless an existing localization
memory already owns the topic) containing:

```text
- lang/{locale}/*.php is the only human-edited translation source.
- en is canonical; en/id key, leaf, placeholder, and plural parity are gated.
- JSON is ignored; TranslationKey is generated, tracked, and CI-verified.
- Laravel translates backend-owned messages; Vue translates frontend copy.
- Public Vue interface: trans, transChoice, useTrans, TranslatedText.
- Global locale source: GeneralSettings.site_locale.
- resources/js/components/ui/**, internal diagnostics, logs, and CLI are excluded.
- Required generation, audit, targeted-test, browser-smoke, and finishing-gate commands.
```

Do not store task progress, review findings, full snippets, ownership state, or
secrets. Report the exact memory name in the final handoff.

- [ ] **Step 7: Prepare handoff without publishing changes**

Return the repository's canonical handoff block with fixed point/final commit,
changed files, inspected/modified symbols, reference verification, commands,
test evidence, review disposition, risks, and Serena memory name. Commit,
push, or PR creation remains a separate developer-authorized action.

---

## Required Acceptance Evidence

- `php artisan lang:export --no-interaction` succeeds twice with no tracked
  drift on the second run.
- `pnpm run test:frontend`, lint, format, and type checks pass.
- Localization feature tests and the two-locale browser smoke test pass.
- `composer run agent:gate` passes after reviewer fixes.
- `git diff` contains no changes beneath `resources/js/components/ui/**`, no
  dependency/schema/public-route change, and no unrelated cleanup.
- Independent review findings are resolved or explicitly accepted.
- The final handoff names the Serena memory created or updated after all other
  completion criteria passed.
