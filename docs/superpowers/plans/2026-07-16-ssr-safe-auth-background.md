# SSR-safe authentication background fallback Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove the SSR `document is not defined` failure on authentication pages and provide a bundled fallback for the split-auth background.

**Architecture:** Keep DOM access encapsulated in `useStyleSettings()` and make it a no-op on the SSR runtime. Resolve the auth background in the existing backend branding boundary, so the Vue split layout receives one URL regardless of whether it is custom media or the committed default asset.

**Tech Stack:** Laravel 13, Inertia Laravel 3, Vue 3, Vite 8, TypeScript, Pest 4, Node test runner.

## Global Constraints

- Fixed point: `497755e4edbb6eb27aa6c4ebc0e8d23741919696`.
- Do not disable SSR or change `config/inertia.php`.
- Do not alter the auth routes, settings schema, upload validation, or media collections.
- Browser globals must only be touched when `typeof document !== 'undefined'`.
- Custom `auth_split_background` media must take precedence over the static fallback.
- Copy, never runtime-reference, the source asset from `nova-starter-kit`.

---

### Task 1: Make the style composable SSR-safe

**Files:**
- Modify: `resources/js/composables/useStyleSettings.ts`
- Create: `tests/Frontend/ssr-style-settings.test.ts`

**Interfaces:**
- Consumes: `usePage().props.style.site_theme` and `.site_font`.
- Produces: browser-only writes to `document.documentElement.dataset.theme` and `.font`.
- SSR contract: `useStyleSettings()` creates no immediate watcher and accesses no DOM global when `document` is undefined.

- [ ] **Step 1: Write the failing contract test**

Create `tests/Frontend/ssr-style-settings.test.ts` with assertions that the
composable has a document availability guard before `watch(` and preserves the
two dataset assignments:

```ts
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const composablePath = new URL(
    '../../resources/js/composables/useStyleSettings.ts',
    import.meta.url,
);

test('style settings avoids DOM access during SSR', async () => {
    const source = await readFile(composablePath, 'utf8');

    assert.match(
        source,
        /if \(typeof document === 'undefined'\) \{\s*return;\s*\}/,
    );
    assert.match(source, /document\.documentElement\.dataset\.theme/);
    assert.match(source, /document\.documentElement\.dataset\.font/);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run:

```bash
node --experimental-strip-types --test tests/Frontend/ssr-style-settings.test.ts
```

Expected: failure because `useStyleSettings.ts` has no document guard.

- [ ] **Step 3: Add the minimal guard**

At the beginning of `useStyleSettings()`, before `const page = usePage()` and
before `watch(...)`, add:

```ts
if (typeof document === 'undefined') {
    return;
}
```

Leave the existing watch source, callback, `{ deep: true, immediate: true }`,
and both dataset assignments unchanged.

- [ ] **Step 4: Run focused checks**

Run:

```bash
node --experimental-strip-types --test tests/Frontend/ssr-style-settings.test.ts
pnpm run types:check
```

Expected: both commands exit `0`.

- [ ] **Step 5: Commit the focused change**

```bash
git add resources/js/composables/useStyleSettings.ts tests/Frontend/ssr-style-settings.test.ts
git commit -m "fix: make style settings SSR-safe"
```

### Task 2: Bundle and resolve the split-auth fallback image

**Files:**
- Create: `public/assets/images/auth-bg.jpg`
- Modify: `app/Support/Branding/BrandingResolver.php`
- Modify: `tests/Feature/Settings/BrandingResolverTest.php`

**Interfaces:**
- Consumes: `SiteBranding::AuthSplitBackground` media collection.
- Produces: `branding.auth_split_background: string`, always a custom media
  URL when present or `/assets/images/auth-bg.jpg` when absent.
- Consumed by: `resources/js/layouts/auth/AuthSplitLayout.vue`.

- [ ] **Step 1: Add a failing fallback test**

In `tests/Feature/Settings/BrandingResolverTest.php`, add a case that creates
or obtains a `SiteBranding` instance without media and asserts:

```php
expect(app(BrandingResolver::class)->resolve($branding))
    ->toHaveKey('auth_split_background', '/assets/images/auth-bg.jpg');
```

Add a second case using the file/media setup already present in this test file
for `SiteBranding::AuthSplitBackground`; assert the returned value equals that
media item's URL and is not `/assets/images/auth-bg.jpg`.

- [ ] **Step 2: Run the focused test to verify the missing-background case fails**

Run:

```bash
php artisan test --compact tests/Feature/Settings/BrandingResolverTest.php
```

Expected: the no-media assertion fails because the resolver currently returns
`null` for `auth_split_background`.

- [ ] **Step 3: Copy the project-local asset**

Run exactly:

```bash
cp /home/tomylana93/projects/nova-starter-kit/public/assets/images/auth-bg.jpg public/assets/images/auth-bg.jpg
```

Then verify it is a regular tracked candidate, not a symlink:

```bash
test -f public/assets/images/auth-bg.jpg
test ! -L public/assets/images/auth-bg.jpg
```

- [ ] **Step 4: Change only the resolver fallback**

In `BrandingResolver::resolve()`, replace the auth background value with the
existing `$this->url(...)` expression followed by:

```php
?? '/assets/images/auth-bg.jpg'
```

Do not change collection names, media conversion names, or any other branding
fallback.

- [ ] **Step 5: Run focused checks**

Run:

```bash
php artisan test --compact tests/Feature/Settings/BrandingResolverTest.php
vendor/bin/pint --dirty --format agent
```

Expected: both resolver cases pass and Pint exits `0`.

- [ ] **Step 6: Commit the focused change**

```bash
git add app/Support/Branding/BrandingResolver.php tests/Feature/Settings/BrandingResolverTest.php public/assets/images/auth-bg.jpg
git commit -m "fix: add auth split background fallback"
```

### Task 3: Verify the actual SSR path and final integration

**Files:**
- Verify only: `resources/js/layouts/AuthLayout.vue`
- Verify only: `resources/js/layouts/auth/AuthSplitLayout.vue`

**Interfaces:**
- `AuthLayout` calls the now SSR-safe composable.
- `AuthSplitLayout` consumes `page.props.branding.auth_split_background` as its
  `backgroundImage`; no layout code change is expected.

- [ ] **Step 1: Confirm the layout boundaries remain unchanged**

Verify that `AuthLayout.vue` still invokes `useStyleSettings()` and selects
`AuthSplitLayout` only for `site_auth_layout === 'split'`. Verify that
`AuthSplitLayout.vue` still applies:

```vue
:style="{
    backgroundImage: `url(${page.props.branding.auth_split_background})`,
}"
```

- [ ] **Step 2: Run the red-capable development SSR smoke test**

In one terminal run:

```bash
composer run dev
```

After Laravel reports it is listening, run in a second terminal:

```bash
curl --fail --silent --show-error http://127.0.0.1:8000/login >/dev/null
```

Expected: curl exits `0`; the first terminal contains neither `SSR ERROR` nor
`document is not defined` for `auth/Login`.

- [ ] **Step 3: Run build and repository gates**

Run:

```bash
pnpm run lint:check
pnpm run format:check
pnpm run types:check
pnpm run test:frontend
pnpm run build:ssr
composer run agent:gate
```

Expected: every command exits `0`.

- [ ] **Step 4: Prepare review package for Gemini**

Provide Gemini the fixed-point SHA, final commit SHA, `git diff
497755e4edbb6eb27aa6c4ebc0e8d23741919696...HEAD`, and these review questions:

1. Does the DOM guard protect every SSR caller without preventing browser
   hydration updates?
2. Does custom auth background media still override the fallback?
3. Is the asset local, tracked, and free of runtime sibling-project coupling?
4. Does the SSR smoke test exercise `/login` and detect the original error?
5. Did the diff avoid changes to auth contracts, settings schema, and SSR
   configuration?

- [ ] **Step 5: Commit only after review approval**

If Tasks 1 and 2 were intentionally committed separately, no additional code
commit is required. Otherwise, after Gemini reports no unresolved P1/P2
findings, commit the reviewed integration with:

```bash
git add resources/js/composables/useStyleSettings.ts app/Support/Branding/BrandingResolver.php tests/Frontend/ssr-style-settings.test.ts tests/Feature/Settings/BrandingResolverTest.php public/assets/images/auth-bg.jpg
git commit -m "fix: make auth SSR safe with background fallback"
```

## Plan self-review

- Spec coverage: Task 1 covers SSR safety and client behavior; Task 2 covers
  local asset, fallback priority, and resolver contract; Task 3 covers the
  original runtime reproduction and final gate.
- Placeholder scan: no unfinished markers or unspecified commands remain.
- Interface consistency: the resolver produces the existing
  `branding.auth_split_background` string prop consumed unchanged by the split
  layout.
