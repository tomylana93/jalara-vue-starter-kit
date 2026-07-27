# Style Settings Page Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the style settings page match the repository's established settings-page layout, navigation, component, and validation patterns without changing backend or upload behavior.

**Architecture:** Keep the existing Inertia page and its data-driven field loop as the only production-code seam. Add layout breadcrumb metadata and compose the existing shadcn-vue Card and Select primitives exactly as the general settings page does; add a focused source-contract test at the repository's existing Node frontend-test seam to prevent regression.

**Tech Stack:** Vue 3, Inertia.js 3, TypeScript, shadcn-vue/reka-ui, Tailwind CSS 4, Node test runner, Pest 4.

## Global Constraints

- Preserve all existing field names, option values, initial values, update endpoint, branding state, upload URLs, accepted file types, and size limits.
- Introduce no route, controller, request validation, settings object, translation, database, authorization, dependency, or generated Wayfinder changes.
- Use existing translated semantic keys and existing UI components only.
- Do not extract a new reusable field component.
- Do not commit unless the developer explicitly requests it.

---

### Task 1: Guard the style settings page contract

**Files:**
- Create: `tests/Frontend/style-settings-page.test.ts`
- Test: `tests/Frontend/style-settings-page.test.ts`

**Interfaces:**
- Consumes: the source contract exported by `resources/js/pages/settings/style/Edit.vue`.
- Produces: regression coverage for breadcrumb route imports and metadata, shadcn-vue Select composition, all five submitted field names, validation wiring, Card composition, and absence of native `<select>` tags.

- [ ] **Step 1: Write the failing source-contract test**

```ts
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const pagePath = new URL(
    '../../resources/js/pages/settings/style/Edit.vue',
    import.meta.url,
);

test('style settings follows the shared settings page contract', async () => {
    const source = await readFile(pagePath, 'utf8');

    assert.match(source, /index as settingsIndex/);
    assert.match(source, /edit as styleEdit/);
    assert.match(source, /title: 'settings\.index\.title'/);
    assert.match(source, /title: 'style\.title'/);
    assert.match(source, /<Card>/);
    assert.match(source, /<Select/);
    assert.match(source, /<SelectTrigger/);
    assert.match(source, /:aria-invalid="invalid\(field\[0\]\)"/);
    assert.match(source, /@blur="validate\(field\[0\]\)"/);
    assert.doesNotMatch(source, /<select(?:\s|>)/i);

    for (const field of [
        'site_logo_style',
        'site_auth_layout',
        'site_layout',
        'site_theme',
        'site_font',
    ]) {
        assert.match(source, new RegExp(`'${field}'`));
    }
});
```

- [ ] **Step 2: Run the test and verify RED**

Run: `pnpm run test:frontend -- tests/Frontend/style-settings-page.test.ts`

Expected: FAIL because the page lacks breadcrumb route imports, Card/Select composition, and validation wiring and still contains a native `<select>`.

### Task 2: Refactor the style settings page

**Files:**
- Modify: `resources/js/pages/settings/style/Edit.vue`
- Test: `tests/Frontend/style-settings-page.test.ts`

**Interfaces:**
- Consumes: `Props`, `AssetField`, the existing update action, uploader actions, `settingsIndex()`, `styleEdit()`, and the Inertia Form slot functions `invalid(field)` and `validate(field)`.
- Produces: layout metadata with `Settings → Style` breadcrumbs and a card-based form whose five select values submit under their existing names.

- [ ] **Step 1: Add the established UI and route imports**

Add imports for `Card`, `CardContent`, `CardDescription`, `CardHeader`, `CardTitle`, `Select`, `SelectContent`, `SelectItem`, `SelectTrigger`, `SelectValue`, `settingsIndex`, and `styleEdit`, matching the ordering used by `resources/js/pages/settings/general/Edit.vue`.

```ts
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as settingsIndex } from '@/routes/settings';
import { edit as styleEdit } from '@/routes/settings/style';
```

- [ ] **Step 2: Add breadcrumb layout metadata**

Replace the existing one-line `defineOptions` call with:

```ts
defineOptions({
    inheritAttrs: false,
    layout: {
        breadcrumbs: [
            {
                title: 'settings.index.title',
                href: settingsIndex(),
            },
            {
                title: 'style.title',
                href: styleEdit(),
            },
        ],
    },
});
```

- [ ] **Step 3: Expose form validation state**

Change the Form slot declaration to:

```vue
#default="{ errors, invalid, validate, processing }"
```

- [ ] **Step 4: Wrap the form contents in the established Card layout**

Use this structure inside the Form, moving the existing field and asset loops into `CardContent`:

```vue
<Card>
    <CardHeader>
        <CardTitle>{{ trans('style.title') }}</CardTitle>
        <CardDescription>{{ trans('style.description') }}</CardDescription>
    </CardHeader>

    <CardContent class="flex flex-col gap-6">
        <!-- Existing field loop, existing asset loop, and submit action -->
    </CardContent>
</Card>
```

- [ ] **Step 5: Replace the native select inside the existing field loop**

Replace only the native select block with:

```vue
<Select
    :name="field[0]"
    :default-value="styleSettings[field[0]]"
>
    <SelectTrigger
        :id="field[0]"
        class="w-full"
        :aria-invalid="invalid(field[0])"
        @blur="validate(field[0])"
    >
        <SelectValue />
    </SelectTrigger>
    <SelectContent>
        <SelectItem
            v-for="option in field[1]"
            :key="option.value"
            :value="option.value"
        >
            {{ option.label }}
        </SelectItem>
    </SelectContent>
</Select>
```

- [ ] **Step 6: Preserve upload controls and align the submit action**

Keep both hidden inputs and the existing Uploader props byte-for-byte equivalent. Wrap the existing Button in:

```vue
<div class="flex items-center gap-4">
    <Button
        :disabled="processing"
        data-test="update-style-settings-button"
    >
        {{ trans('style.action.save') }}
    </Button>
</div>
```

- [ ] **Step 7: Run the frontend contract test and verify GREEN**

Run: `pnpm run test:frontend -- tests/Frontend/style-settings-page.test.ts`

Expected: PASS.

- [ ] **Step 8: Run focused backend regression tests**

Run:

```bash
php artisan test --compact \
  tests/Feature/Settings/StyleSettingsAccessTest.php \
  tests/Feature/Settings/UpdateStyleSettingsTest.php \
  tests/Feature/Settings/StyleSettingsUploadTest.php
```

Expected: all focused style-settings and branding tests PASS.

### Task 3: Format, verify, and review

**Files:**
- Modify mechanically if required: `resources/js/pages/settings/style/Edit.vue`
- Modify mechanically if required: `tests/Frontend/style-settings-page.test.ts`

**Interfaces:**
- Consumes: the completed refactor and regression test.
- Produces: a formatted, linted, type-safe, built, reviewed diff with no unrelated changes.

- [ ] **Step 1: Run safe frontend formatting and lint fixes**

Run:

```bash
pnpm run lint
pnpm run format
```

Expected: both commands exit successfully; only task-owned files may change.

- [ ] **Step 2: Re-run focused verification**

Run:

```bash
pnpm run test:frontend
pnpm run types:check
php artisan test --compact \
  tests/Feature/Settings/StyleSettingsAccessTest.php \
  tests/Feature/Settings/UpdateStyleSettingsTest.php \
  tests/Feature/Settings/StyleSettingsUploadTest.php
```

Expected: every command PASS.

- [ ] **Step 3: Run the repository finishing gate**

Wayfinder generation is not required because no route, controller, invokable action, route name, or route parameter changes.

Run:

```bash
vendor/bin/pint --dirty --format agent
composer run agent:gate
```

Expected: Pint exits successfully and the agent gate passes lint checking, format checking, type checking, tests, and production build.

- [ ] **Step 4: Review the final diff**

Run:

```bash
git diff --check
git diff --stat
git diff -- docs/superpowers/specs/2026-07-16-style-settings-page-refactor-design.md \
  docs/superpowers/plans/2026-07-16-style-settings-page-refactor.md \
  resources/js/pages/settings/style/Edit.vue \
  tests/Frontend/style-settings-page.test.ts
```

Expected: no whitespace errors, no unrelated files, and all acceptance criteria visibly covered.

- [ ] **Step 5: Obtain independent review**

Use the repository's `code-review` workflow against fixed point `32269b511d686bbec089cbc9ac3a4c88b8fbc952`. Resolve actionable findings, then rerun the smallest affected check and `composer run agent:gate`.

- [ ] **Step 6: Leave changes uncommitted**

Report the final diff and verification evidence. Do not create a commit or pull request unless the developer explicitly requests one.
