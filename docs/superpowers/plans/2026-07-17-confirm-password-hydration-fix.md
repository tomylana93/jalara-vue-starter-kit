# Confirm Password Hydration Fix Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Eliminate the password-confirmation page's SSR hydration mismatch, lock the behavior down with a browser regression test, and keep Playwright CLI session artifacts out of Git.

**Architecture:** Correct the invalid label attribute at its single call site instead of expanding the shared label wrapper's API. Exercise the protected Inertia page through Pest Browser for runtime smoke coverage and use a focused frontend source-contract test for the exact red-capable attribute regression, because hydrated DOM and raw feature responses normalize the defect in the test environment. Treat `.playwright-cli` as disposable root-level development output.

**Tech Stack:** Laravel, Fortify password confirmation, Inertia.js v3, Vue 3 SSR, Reka UI, Pest 4 Browser, Playwright CLI

## Global Constraints

- Do not modify the shared label wrapper.
- Add no dependencies and make no changes to routes, controllers, authorization, database schema, generated Wayfinder files, or shared UI APIs.
- Do not address hydration warnings on unrelated settings pages.
- Do not commit Playwright CLI traces, snapshots, screenshots, videos, or console logs as fixtures.
- Follow test-driven development: observe the source-contract test fail before changing the Vue component; retain the browser test as runtime smoke coverage.

---

### Task 1: Correct and Lock Down Password-Confirmation Hydration

**Files:**
- Create: `tests/Browser/Auth/PasswordConfirmationTest.php`
- Create: `tests/Frontend/confirm-password-markup.test.ts`
- Modify: `resources/js/pages/auth/ConfirmPassword.vue:45`
- Reference: `tests/Browser/SmokeTest.php`
- Reference: `tests/Feature/Auth/PasswordConfirmationTest.php`

**Interfaces:**
- Consumes: Laravel's named `password.confirm` route, `App\Models\User` factory, the existing authenticated Pest browser session, and Pest Browser's `visit()`, `assertSee()`, `assertNoJavaScriptErrors()`, and `assertNoConsoleLogs()` APIs.
- Produces: A password label whose rendered DOM association is `for="password"` on both server and client, a red-capable source-contract regression test, and browser smoke coverage for the protected page.

- [ ] **Step 1: Create the failing source-contract test and browser smoke test**

Create `tests/Frontend/confirm-password-markup.test.ts` with:

```typescript
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const componentPath = new URL(
    '../../resources/js/pages/auth/ConfirmPassword.vue',
    import.meta.url,
);

test('password confirmation label uses the Vue for attribute', async () => {
    const source = await readFile(componentPath, 'utf8');

    assert.match(source, /<Label for="password">/);
    assert.doesNotMatch(source, /\bhtmlFor=/);
});
```

Create `tests/Browser/Auth/PasswordConfirmationTest.php` with:

```php
<?php

use App\Models\User;

test('password confirmation page hydrates without browser errors', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit(route('password.confirm'))
        ->assertSee('Confirm password')
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();
});
```

- [ ] **Step 2: Run the source-contract test and confirm the diagnosed failure**

Run:

```bash
node --experimental-strip-types --test tests/Frontend/confirm-password-markup.test.ts
```

Expected: FAIL because the current component uses `htmlFor="password"`, so the required `<Label for="password">` markup is absent. This source-contract seam is used because browser DOM and raw feature-response probes normalize the attribute and were empirically unable to distinguish RED from GREEN.

- [ ] **Step 3: Apply the minimal Vue fix**

In `resources/js/pages/auth/ConfirmPassword.vue`, replace:

```vue
<Label htmlFor="password">{{
    trans('auth.confirm_password.label.password')
}}</Label>
```

with:

```vue
<Label for="password">{{
    trans('auth.confirm_password.label.password')
}}</Label>
```

- [ ] **Step 4: Run the focused source-contract and browser tests**

Run:

```bash
node --experimental-strip-types --test tests/Frontend/confirm-password-markup.test.ts
php artisan test --compact tests/Browser/Auth/PasswordConfirmationTest.php
```

Expected: the source-contract test PASSes, and the browser smoke test PASSes with no JavaScript errors or console logs.

- [ ] **Step 5: Run the existing password-confirmation feature tests**

Run:

```bash
php artisan test --compact tests/Feature/Auth/PasswordConfirmationTest.php
```

Expected: PASS, confirming the authenticated render and unauthenticated redirect still behave as before.

- [ ] **Step 6: Format the modified PHP and frontend files**

Run:

```bash
vendor/bin/pint --dirty --format agent
pnpm run format
```

Expected: both commands exit successfully; formatting changes remain limited to the files in this task.

- [ ] **Step 7: Re-run the focused tests after formatting**

Run:

```bash
php artisan test --compact tests/Browser/Auth/PasswordConfirmationTest.php
php artisan test --compact tests/Feature/Auth/PasswordConfirmationTest.php
node --experimental-strip-types --test tests/Frontend/confirm-password-markup.test.ts
```

Expected: both commands PASS.

- [ ] **Step 8: Commit the hydration fix and regression test when a commit is explicitly authorized**

```bash
git add resources/js/pages/auth/ConfirmPassword.vue tests/Browser/Auth/PasswordConfirmationTest.php tests/Frontend/confirm-password-markup.test.ts
git commit -m "fix: prevent password confirmation hydration mismatch"
```

Do not perform this step unless the developer has explicitly requested a commit.

### Task 2: Ignore and Remove Playwright CLI Session Artifacts

**Files:**
- Modify: `.gitignore`
- Delete locally: `.playwright-cli/console-2026-07-17T06-36-35-064Z.log`
- Delete locally: `.playwright-cli/page-2026-07-17T06-36-35-545Z.yml`

**Interfaces:**
- Consumes: Git's repository-root ignore syntax and the untracked `.playwright-cli` directory created during diagnosis.
- Produces: A repository rule that ignores every local `.playwright-cli` session artifact without affecting `tests/Browser` or its intentional output directories.

- [ ] **Step 1: Verify the generated artifacts are still untracked**

Run:

```bash
git status --short -- .playwright-cli
git ls-files .playwright-cli
```

Expected: the first command shows only `?? .playwright-cli/`; the second command produces no output. Stop before deletion if any file is tracked.

- [ ] **Step 2: Add the root-level ignore rule**

Append this entry to `.gitignore` alongside the other development-tool output rules:

```gitignore
/.playwright-cli/
```

- [ ] **Step 3: Prove the ignore rule covers a representative artifact**

Run:

```bash
git check-ignore -v .playwright-cli/console-2026-07-17T06-36-35-064Z.log
```

Expected: output points to the new `/.playwright-cli/` rule in `.gitignore`.

- [ ] **Step 4: Remove the current untracked session artifacts**

Run:

```bash
rm -rf -- .playwright-cli
```

Expected: `.playwright-cli` no longer exists. This deletion is limited to the two untracked diagnostic artifacts verified in Step 1.

- [ ] **Step 5: Confirm repository hygiene**

Run:

```bash
test ! -e .playwright-cli
git status --short
git diff --check
```

Expected: the directory-absence check and `git diff --check` succeed; Git status shows `.gitignore`, the Vue fix, the new browser test, and the approved spec/plan documents only.

- [ ] **Step 6: Run the repository finishing gate**

Wayfinder generation is not required because no route, controller, invokable action, route name, or route parameter changes.

Run in order:

```bash
vendor/bin/pint --dirty --format agent
pnpm run lint
pnpm run format
php artisan test --compact tests/Browser/Auth/PasswordConfirmationTest.php
php artisan test --compact tests/Feature/Auth/PasswordConfirmationTest.php
composer run agent:gate
```

Expected: every command exits successfully. If a logic, type, or test failure occurs, use systematic debugging, rerun the smallest failing check, then rerun this full gate.

- [ ] **Step 7: Review the final diff against the fixed point**

Run:

```bash
git diff --stat 349d1e1c21788fd9da3b17798f2aedfc57f99515
git diff 349d1e1c21788fd9da3b17798f2aedfc57f99515 -- .gitignore resources/js/pages/auth/ConfirmPassword.vue tests/Browser/Auth/PasswordConfirmationTest.php tests/Frontend/confirm-password-markup.test.ts docs/superpowers/specs/2026-07-17-confirm-password-hydration-fix-design.md docs/superpowers/plans/2026-07-17-confirm-password-hydration-fix.md
```

Expected: the diff contains only the approved hydration correction, regression test, ignore rule, and documentation. No Serena memory update is needed because the fix does not establish a durable architectural or domain decision.

- [ ] **Step 8: Commit repository hygiene when a commit is explicitly authorized**

If Task 1 was already committed separately, run:

```bash
git add .gitignore docs/superpowers/specs/2026-07-17-confirm-password-hydration-fix-design.md docs/superpowers/plans/2026-07-17-confirm-password-hydration-fix.md
git commit -m "chore: ignore Playwright CLI artifacts"
```

Do not perform this step unless the developer has explicitly requested a commit.
