# Profile and Security Settings Layout Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `jalara-agentic-workflow` with Claude/Anthropic as the sole writer. Execute this plan task-by-task; reviewers remain read-only. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give `/profile` and `/security` a consistent `PageWrapper` shell and shared account-settings navigation without changing profile or authentication behavior.

**Architecture:** Keep the global `AppLayout` selected by `resources/js/app.ts`. Each page explicitly renders `PageWrapper` as its outer shell and `SettingsLayout` inside it; `SettingsLayout` owns only responsive Profile/Security navigation and its content slot. Preserve all existing Wayfinder actions, Inertia forms, component props, and backend contracts.

**Tech Stack:** Laravel 13, Inertia.js 3, Vue 3 with TypeScript, Wayfinder, Fortify 1, Tailwind CSS 4, Pest 4.

## Global Constraints

- Claude/Anthropic is the only writer; Google is the final read-only reviewer and OpenAI remains orchestrator.
- Start from a clean feature branch named `claude/profile-security-settings-layout` descending from `dev`; never implement on `dev` or `main`.
- Preserve `/profile`, `/security`, `profile.edit`, `security.edit`, all controller actions, validation behavior, breadcrumbs, avatar behavior, password resets, 2FA, and passkeys.
- Do not change dependencies, routes, controllers, requests, migrations, Fortify configuration, or the global Inertia layout resolver.
- Use Wayfinder route/action functions; do not add hardcoded application URLs.
- Vue page templates must have one root component. Put `<Head>` inside the root `PageWrapper`.
- Use Tailwind CSS 4 utilities and `gap-*` for sibling spacing.
- Do not alter `PageWrapper.vue` unless a reproducible defect blocks the specified composition; current inspection found no required change.
- Every implementation task follows red-green-refactor and ends with an independently testable commit.
- Run `composer agent:gate` before review and provide fixed-point evidence to the Google reviewer.

---

## File Map

| File | Responsibility | Planned action |
|---|---|---|
| `resources/js/layouts/settings/Layout.vue` | Responsive account-settings navigation and content slot | Replace the untracked draft with the approved focused layout |
| `resources/js/pages/Profile.vue` | Avatar and profile-information form | Compose `PageWrapper` and `SettingsLayout`; preserve behavior |
| `resources/js/pages/Security.vue` | Password, 2FA, and passkey management | Compose `PageWrapper` and `SettingsLayout`; preserve behavior |
| `lang/en/settings.php` | English settings copy | Add layout, navigation, profile, and security keys |
| `lang/id/settings.php` | Indonesian settings copy | Add matching translated keys |
| `tests/Feature/Profile/ProfileSettingsLayoutTest.php` | Profile page composition contract | Create focused source-contract tests |
| `tests/Feature/Security/SecuritySettingsLayoutTest.php` | Security page composition contract | Create focused source-contract tests |
| `tests/Feature/Settings/AccountSettingsLayoutTest.php` | Shared layout and translation contract | Create focused layout tests |

---

### Task 0: Establish the Claude writer workspace

**Files:** None.

**Interfaces:**
- Consumes: `origin/dev` and the approved design at `docs/superpowers/specs/2026-07-15-profile-security-settings-layout-refactor-design.md`.
- Produces: one clean feature branch with Claude as its only writer and a recorded fixed point.

- [ ] **Step 1: Verify the current checkout without modifying it**

Run:

```bash
git status --short --branch
git branch --show-current
git merge-base --is-ancestor origin/dev HEAD
FIXED_POINT=$(git rev-parse origin/dev)
git show --no-patch --oneline "$FIXED_POINT"
```

Expected: the current source branch is `dev`, the ancestry command exits `0`, and the last command prints the fixed-point commit. The current checkout may show the user-owned untracked `resources/js/layouts/settings/Layout.vue` and planning documents; do not delete or overwrite them while creating the writer workspace.

- [ ] **Step 2: Create or switch to the isolated writer branch**

Use the repository's normal clean-worktree/worktree flow and create:

```text
claude/profile-security-settings-layout
```

Expected: `git branch --show-current` prints `claude/profile-security-settings-layout`, `git merge-base --is-ancestor origin/dev HEAD` exits `0`, and `git status --short` contains only files intentionally carried into this task.

- [ ] **Step 3: Record ownership and fixed point in the implementation handoff**

Record:

```yaml
writer: anthropic
reviewer: google
fixed_point: $FIXED_POINT
base_branch: dev
working_branch: claude/profile-security-settings-layout
```

Expected: no source code has been changed and only Claude has write ownership.

---

### Task 1: Repair the shared account-settings layout

**Files:**
- Create: `tests/Feature/Settings/AccountSettingsLayoutTest.php`
- Modify/add: `resources/js/layouts/settings/Layout.vue`
- Modify: `lang/en/settings.php`
- Modify: `lang/id/settings.php`

**Interfaces:**
- Consumes: `useCurrentUrl().isCurrentOrParentUrl`, `useTrans().trans`, `toUrl`, `profile.edit`, and `security.edit`.
- Produces: a default-slot Vue component named `SettingsLayout` by import convention, translated navigation, and an `aria-current` contract used by both pages.

- [ ] **Step 1: Generate the focused Pest test**

Run:

```bash
php artisan make:test --pest Settings/AccountSettingsLayoutTest --no-interaction
```

Expected: `tests/Feature/Settings/AccountSettingsLayoutTest.php` is created.

- [ ] **Step 2: Replace the generated test with the failing contract**

Use exactly:

```php
<?php

test('the account settings layout has accessible wayfinder navigation', function (): void {
    $layout = file_get_contents(resource_path('js/layouts/settings/Layout.vue'));

    expect($layout)
        ->toContain("from '@/routes/profile'")
        ->toContain("from '@/routes/security'")
        ->toContain(':aria-current=')
        ->toContain("'page'")
        ->not->toContain("import Heading from '@/components/Heading.vue'")
        ->not->toContain('<component :is="item.icon"');
});

test('account settings navigation copy is translated', function (string $locale, array $expected): void {
    expect(__('settings.layout.aria', [], $locale))->toBe($expected['aria'])
        ->and(__('settings.nav.profile', [], $locale))->toBe($expected['profile'])
        ->and(__('settings.nav.security', [], $locale))->toBe($expected['security'])
        ->and(__('settings.profile.heading', [], $locale))->toBe($expected['profile_heading'])
        ->and(__('settings.security.heading', [], $locale))->toBe($expected['security_heading']);
})->with([
    'English' => ['en', [
        'aria' => 'Account settings',
        'profile' => 'Profile',
        'security' => 'Security',
        'profile_heading' => 'Profile settings',
        'security_heading' => 'Security settings',
    ]],
    'Indonesian' => ['id', [
        'aria' => 'Pengaturan akun',
        'profile' => 'Profil',
        'security' => 'Keamanan',
        'profile_heading' => 'Pengaturan profil',
        'security_heading' => 'Pengaturan keamanan',
    ]],
]);
```

- [ ] **Step 3: Run the new test and verify red**

Run:

```bash
php artisan test --compact tests/Feature/Settings/AccountSettingsLayoutTest.php
```

Expected: FAIL because the draft layout lacks `aria-current`, still imports `Heading`, renders `item.icon`, and the translation keys resolve to their untranslated key names.

- [ ] **Step 4: Replace `resources/js/layouts/settings/Layout.vue` with the focused layout**

Use exactly:

```vue
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTrans } from '@/composables/useTrans';
import { toUrl } from '@/lib/utils';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const { trans } = useTrans();
const { isCurrentOrParentUrl } = useCurrentUrl();

const navigationItems = computed<NavItem[]>(() => [
    {
        title: trans('settings.nav.profile'),
        href: editProfile(),
    },
    {
        title: trans('settings.nav.security'),
        href: editSecurity(),
    },
]);
</script>

<template>
    <div class="flex flex-col gap-6 lg:flex-row lg:gap-12">
        <aside class="w-full lg:w-48 lg:shrink-0">
            <nav
                class="flex flex-col gap-1"
                :aria-label="trans('settings.layout.aria')"
            >
                <Button
                    v-for="item in navigationItems"
                    :key="toUrl(item.href)"
                    variant="ghost"
                    :class="[
                        'w-full justify-start',
                        { 'bg-muted': isCurrentOrParentUrl(item.href) },
                    ]"
                    as-child
                >
                    <Link
                        :href="item.href"
                        :aria-current="
                            isCurrentOrParentUrl(item.href)
                                ? 'page'
                                : undefined
                        "
                    >
                        {{ item.title }}
                    </Link>
                </Button>
            </nav>
        </aside>

        <Separator class="lg:hidden" />

        <div class="min-w-0 flex-1 md:max-w-2xl">
            <section class="max-w-xl space-y-12">
                <slot />
            </section>
        </div>
    </div>
</template>
```

- [ ] **Step 5: Add exact English translation keys**

Insert after `'sidebar' => 'Settings',` in `lang/en/settings.php`:

```php
    'layout' => [
        'aria' => 'Account settings',
    ],
    'nav' => [
        'profile' => 'Profile',
        'security' => 'Security',
    ],
    'profile' => [
        'title' => 'Profile settings',
        'heading' => 'Profile settings',
        'description' => 'Update your profile information and avatar.',
    ],
    'security' => [
        'title' => 'Security settings',
        'heading' => 'Security settings',
        'description' => 'Manage your password and sign-in security.',
    ],
```

- [ ] **Step 6: Add exact Indonesian translation keys**

Insert after `'sidebar' => 'Pengaturan',` in `lang/id/settings.php`:

```php
    'layout' => [
        'aria' => 'Pengaturan akun',
    ],
    'nav' => [
        'profile' => 'Profil',
        'security' => 'Keamanan',
    ],
    'profile' => [
        'title' => 'Pengaturan profil',
        'heading' => 'Pengaturan profil',
        'description' => 'Perbarui informasi profil dan avatar Anda.',
    ],
    'security' => [
        'title' => 'Pengaturan keamanan',
        'heading' => 'Pengaturan keamanan',
        'description' => 'Kelola kata sandi dan keamanan masuk Anda.',
    ],
```

- [ ] **Step 7: Format PHP and verify green**

Run:

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact tests/Feature/Settings/AccountSettingsLayoutTest.php
pnpm exec prettier --write resources/js/layouts/settings/Layout.vue
pnpm run lint:check
pnpm run types:check
```

Expected: the Pest file passes, Pint reports no remaining formatting changes, and frontend lint/type checks exit `0`.

- [ ] **Step 8: Commit the independently working layout**

Run:

```bash
git add resources/js/layouts/settings/Layout.vue lang/en/settings.php lang/id/settings.php tests/Feature/Settings/AccountSettingsLayoutTest.php
git commit -m "refactor: add shared account settings layout"
```

Expected: one commit containing only the shared layout, translations, and its test.

---

### Task 2: Compose the profile page with both wrappers

**Files:**
- Create: `tests/Feature/Profile/ProfileSettingsLayoutTest.php`
- Modify: `resources/js/pages/Profile.vue`

**Interfaces:**
- Consumes: `PageWrapper` props `title` and `description`, the default `SettingsLayout` slot, and `settings.profile.*` translations from Task 1.
- Produces: one-root Profile page composition while preserving every existing form/action interface.

- [ ] **Step 1: Generate the profile composition test**

Run:

```bash
php artisan make:test --pest Profile/ProfileSettingsLayoutTest --no-interaction
```

Expected: `tests/Feature/Profile/ProfileSettingsLayoutTest.php` is created.

- [ ] **Step 2: Write the failing profile contract**

Use exactly:

```php
<?php

test('the profile page uses the page wrapper and account settings layout', function (): void {
    $profilePage = file_get_contents(resource_path('js/pages/Profile.vue'));

    expect($profilePage)
        ->toContain("import PageWrapper from '@/components/PageWrapper.vue'")
        ->toContain("import SettingsLayout from '@/layouts/settings/Layout.vue'")
        ->toContain("trans('settings.profile.title')")
        ->toContain("trans('settings.profile.heading')")
        ->toContain("trans('settings.profile.description')")
        ->toContain('<PageWrapper')
        ->toContain('<SettingsLayout>')
        ->not->toContain('<h1 class="sr-only">Profile settings</h1>');
});
```

- [ ] **Step 3: Run the profile contract and verify red**

Run:

```bash
php artisan test --compact tests/Feature/Profile/ProfileSettingsLayoutTest.php
```

Expected: FAIL because `Profile.vue` does not yet import or render either wrapper and still contains the visually hidden `h1`.

- [ ] **Step 4: Add the required Profile imports and translation composable**

Add these imports beside the existing component/composable imports:

```ts
import PageWrapper from '@/components/PageWrapper.vue';
import { useTrans } from '@/composables/useTrans';
import SettingsLayout from '@/layouts/settings/Layout.vue';
```

After `const props = defineProps<Props>();`, add:

```ts
const { trans } = useTrans();
```

Do not change any existing imports, props, computed values, refs, Wayfinder functions, or form behavior beyond adding these lines.

- [ ] **Step 5: Replace only Profile's page-shell markup**

Make the following exact structural changes:

```diff
 <template>
-    <Head title="Profile settings" />
-
-    <h1 class="sr-only">Profile settings</h1>
-
-    <div class="flex flex-col space-y-6">
+    <PageWrapper
+        :title="trans('settings.profile.heading')"
+        :description="trans('settings.profile.description')"
+    >
+        <Head :title="trans('settings.profile.title')" />
+
+        <SettingsLayout>
+            <div class="flex flex-col gap-6">
         <Heading
             variant="small"
-            title="Profile"
+            title="Profile information"
             description="Update your name, email address, and phone number"
         />
```

At the current end of the template, replace the single closing page wrapper:

```diff
-    </div>
+            </div>
+        </SettingsLayout>
+    </PageWrapper>
 </template>
```

The avatar block, hidden upload ID, all form fields, validation bindings, verification message, and save button between those wrapper changes must remain byte-for-byte behaviorally equivalent.

- [ ] **Step 6: Format and run the focused profile checks**

Run:

```bash
pnpm exec prettier --write resources/js/pages/Profile.vue
php artisan test --compact tests/Feature/Profile/ProfileSettingsLayoutTest.php
php artisan test --compact tests/Feature/Profile/ProfileUpdateTest.php
php artisan test --compact tests/Feature/Profile/AvatarUploadTest.php
pnpm run lint:check
pnpm run types:check
```

Expected: all three Pest files pass and both frontend checks exit `0`. The pre-existing test that defers email validation must remain green.

- [ ] **Step 7: Commit the profile composition**

Run:

```bash
git add resources/js/pages/Profile.vue tests/Feature/Profile/ProfileSettingsLayoutTest.php
git commit -m "refactor: compose profile settings layout"
```

Expected: one commit containing only the profile page and its focused test.

---

### Task 3: Compose all security sections with both wrappers

**Files:**
- Create: `tests/Feature/Security/SecuritySettingsLayoutTest.php`
- Modify: `resources/js/pages/Security.vue`

**Interfaces:**
- Consumes: `PageWrapper`, the default `SettingsLayout` slot, and `settings.security.*` translations from Task 1.
- Produces: one-root Security page containing password, 2FA, and passkey sections without changing their interfaces.

- [ ] **Step 1: Generate the security composition test**

Run:

```bash
php artisan make:test --pest Security/SecuritySettingsLayoutTest --no-interaction
```

Expected: `tests/Feature/Security/SecuritySettingsLayoutTest.php` is created.

- [ ] **Step 2: Write the failing security contract**

Use exactly:

```php
<?php

test('the complete security page uses the page wrapper and account settings layout', function (): void {
    $securityPage = file_get_contents(resource_path('js/pages/Security.vue'));

    expect($securityPage)
        ->toContain("import PageWrapper from '@/components/PageWrapper.vue'")
        ->toContain("import SettingsLayout from '@/layouts/settings/Layout.vue'")
        ->toContain("trans('settings.security.title')")
        ->toContain("trans('settings.security.heading')")
        ->toContain("trans('settings.security.description')")
        ->toContain('<PageWrapper')
        ->toContain('<SettingsLayout>')
        ->toContain('<ManageTwoFactor')
        ->toContain('<ManagePasskeys')
        ->not->toContain('<h1 class="sr-only">Security settings</h1>');
});
```

- [ ] **Step 3: Run the security contract and verify red**

Run:

```bash
php artisan test --compact tests/Feature/Security/SecuritySettingsLayoutTest.php
```

Expected: FAIL because `Security.vue` does not yet use the wrappers or translations and still contains its visually hidden `h1`.

- [ ] **Step 4: Add the required Security imports and translation composable**

Add:

```ts
import PageWrapper from '@/components/PageWrapper.vue';
import { useTrans } from '@/composables/useTrans';
import SettingsLayout from '@/layouts/settings/Layout.vue';
```

After `const props = defineProps<Props>();`, add:

```ts
const { trans } = useTrans();
```

Keep the existing `SecurityController`, password, 2FA, passkey, and route imports unchanged.

- [ ] **Step 5: Put the entire Security page under one composition root**

Replace the current top-level `<Head>`, hidden `h1`, and first password wrapper opening with:

```vue
<template>
    <PageWrapper
        :title="trans('settings.security.heading')"
        :description="trans('settings.security.description')"
    >
        <Head :title="trans('settings.security.title')" />

        <SettingsLayout>
            <div class="space-y-12">
                <div class="space-y-6">
                    <Heading
                        variant="small"
                        title="Update password"
                        description="Ensure your account is using a long, random password to stay secure"
                    />
```

Keep the existing password `<Form>` and all of its fields unchanged inside that inner `space-y-6` section. Immediately after the password form closes, close that section and retain both existing child components inside the `space-y-12` stack:

```vue
                </div>

                <ManageTwoFactor
                    :canManageTwoFactor="canManageTwoFactor"
                    :requiresConfirmation="requiresConfirmation"
                    :twoFactorEnabled="twoFactorEnabled"
                />

                <ManagePasskeys
                    :canManagePasskeys="canManagePasskeys"
                    :passkeys="passkeys"
                />
            </div>
        </SettingsLayout>
    </PageWrapper>
</template>
```

Delete the old top-level `ManageTwoFactor` and `ManagePasskeys` copies so each component renders exactly once. Preserve their prop names and values exactly as shown.

- [ ] **Step 6: Format and run the focused security checks**

Run:

```bash
pnpm exec prettier --write resources/js/pages/Security.vue
php artisan test --compact tests/Feature/Security/SecuritySettingsLayoutTest.php
php artisan test --compact tests/Feature/Security/SecurityTest.php
pnpm run lint:check
pnpm run types:check
```

Expected: both Pest files pass and frontend checks exit `0`. Password update, password confirmation, disabled Fortify features, 2FA props, and passkey props remain covered by the existing security test.

- [ ] **Step 7: Commit the security composition**

Run:

```bash
git add resources/js/pages/Security.vue tests/Feature/Security/SecuritySettingsLayoutTest.php
git commit -m "refactor: compose security settings layout"
```

Expected: one commit containing only the security page and its focused test.

---

### Task 4: Verify the integrated refactor and prepare cross-provider review

**Files:** No new source files. Update the system-of-record handoff rather than creating another repository document unless explicitly requested.

**Interfaces:**
- Consumes: Tasks 1–3 and the approved acceptance criteria.
- Produces: deterministic gate evidence and a fixed-point diff for a read-only Google reviewer.

- [ ] **Step 1: Run all focused backend and contract tests together**

Run:

```bash
php artisan test --compact tests/Feature/Settings/AccountSettingsLayoutTest.php tests/Feature/Profile/ProfileSettingsLayoutTest.php tests/Feature/Profile/ProfileUpdateTest.php tests/Feature/Profile/AvatarUploadTest.php tests/Feature/Security/SecuritySettingsLayoutTest.php tests/Feature/Security/SecurityTest.php
```

Expected: all selected tests pass with zero failures.

- [ ] **Step 2: Run formatting and frontend static checks**

Run:

```bash
vendor/bin/pint --dirty --format agent
pnpm run format:check
pnpm run lint:check
pnpm run types:check
```

Expected: every command exits `0`. If Pint changes a task-owned PHP file, rerun the focused tests and commit only that formatting change with the owning task.

- [ ] **Step 3: Perform manual presentation checks**

With the local application running, verify both `/profile` and `/security` at a narrow mobile viewport and at a viewport at least `1024px` wide:

```text
- one visible page h1
- navigation before content on mobile
- navigation beside content at lg width
- correct active-link background and aria-current="page"
- keyboard-visible focus on both navigation links
- no horizontal overflow
- correct light and dark appearance
- profile save and avatar controls remain usable
- password save, 2FA controls, and passkey controls remain usable when enabled
```

Expected: every item is observed or a specific environment limitation is recorded in the handoff.

- [ ] **Step 4: Run the mandatory Jalara gate**

Run:

```bash
composer agent:gate
```

Expected: frontend lint, formatting, Vue type checking, PHP tests/static checks, and the production build all pass. Do not claim completion from targeted tests alone.

- [ ] **Step 5: Confirm the diff is scoped and capture the fixed point**

Run:

```bash
git status --short
git diff --check origin/dev...HEAD
git diff --stat origin/dev...HEAD
REVIEW_FIXED_POINT=$(git rev-parse HEAD)
git show --no-patch --oneline "$REVIEW_FIXED_POINT"
```

Expected: no unintended files, no whitespace errors, a diff limited to the declared scope, and the reviewed commit stored in `REVIEW_FIXED_POINT`.

- [ ] **Step 6: Send a read-only review packet to Google**

Use this exact review scope:

```yaml
review:
  provider: google
  mode: read_only
  base: origin/dev
  fixed_point: $REVIEW_FIXED_POINT
  axes:
    - approved specification and acceptance criteria
    - repository conventions and AGENTS.md
    - responsive layout and accessibility regressions
    - preservation of profile, password, 2FA, and passkey behavior
  forbidden_action: modify source files
```

Expected: findings use `.agents/contracts/review-finding.md`; preferences without concrete impact are not findings. Claude addresses P0–P2 findings and reruns affected checks, with at most two cycles for an identical gate failure.

- [ ] **Step 7: Publish the final Jalara handoff**

Publish a handoff conforming to `.agents/contracts/handoff.md` that includes:

```yaml
handoff:
  task_id: USER-2026-07-15-profile-security-layout
  phase_completed: implementation_and_independent_review
  status: ready_for_pull_request
  decisions:
    - decision: PageWrapper is the outer shell and SettingsLayout owns only account navigation.
      rationale: Preserves the existing global layout while preventing duplicate page-shell concerns.
      source: docs/superpowers/specs/2026-07-15-profile-security-settings-layout-refactor-design.md
  changes:
    files_modified: []
    files_created: []
    files_deleted: []
  evidence:
    documentation_consulted: []
    commands_run: []
    tests_passed: []
    gates_passed: []
  risks:
    known: []
    unresolved: []
  next:
    owner: human
    action: approve pull request to dev
    fixed_point: $REVIEW_FIXED_POINT
```

Replace every empty evidence/change array with actual facts from the implementation. Do not leave placeholders in the published handoff.

- [ ] **Step 8: Open the pull request to `dev`**

After the review passes, push the Claude-owned branch and open a pull request targeting `dev`. The PR body must link the approved spec and plan, include gate evidence, summarize manual checks, and list the independent reviewer outcome.

Expected: the PR targets `dev`; human merge approval remains required.

---

## Plan Self-Review

- Every acceptance criterion maps to Tasks 1–4.
- No backend, route, schema, dependency, Fortify configuration, or global-layout change is planned.
- `PageWrapper.vue` is intentionally unchanged because its current API supports the composition.
- Profile and Security use identical wrapper ordering while retaining distinct content.
- All generated tests have exact file paths, bodies, red commands, and green commands.
- The final gate and non-Anthropic read-only review are mandatory before PR handoff.
