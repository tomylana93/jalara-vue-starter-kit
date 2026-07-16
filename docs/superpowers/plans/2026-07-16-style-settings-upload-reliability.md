# Style Settings Upload Reliability Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Repair the local upload regressions, update the browser favicon without reload, render Profile initials after deletion, place the Profile avatar on the right at large widths, and preserve SSR safety.

**Architecture:** Keep staged upload, parent-form promotion, and queued conversions intact. `Uploader` will publish its existing failure messages as a typed event. Style Settings consumes that event, uses compact FilePond previews, and declares a keyed Inertia favicon link. Profile uses a static responsive Tailwind grid and always mounts its Avatar fallback.

**Tech Stack:** Laravel 12, Inertia v3, Vue 3, TypeScript, FilePond, Spatie Media Library, Pest, Node test runner, Tailwind CSS v4.

## Global Constraints

- Do not change storage, authorization, routes, controllers, staged upload cleanup, promotion, or queued conversion behavior.
- Favicon formats remain PNG, WebP, and ICO; its maximum remains 1 MiB.
- New browser-only logic must stay inside mounted/event callbacks. SSR render paths may not read `window`, `document`, `File`, `XMLHttpRequest`, or FilePond APIs.
- Use static Tailwind responsive classes for the Profile layout; no viewport JavaScript.
- Implement code on a task branch, not `main` or `dev`.

---

## File Map

| File | Responsibility |
| --- | --- |
| `resources/js/components/uploader/Uploader.vue` | Typed, consumer-facing staging failure event. |
| `resources/js/pages/settings/style/Edit.vue` | Compact previews, ICO types, visible asset errors, keyed favicon head entry. |
| `resources/js/pages/Profile.vue` | SSR-safe responsive form composition and unconditional initials fallback. |
| `tests/Feature/Settings/StyleSettingsUploadTest.php` | Favicon type validation. |
| `tests/Frontend/style-settings-page.test.ts` | Style Settings markup contract. |
| `tests/Frontend/profile-page.test.ts` | Profile fallback and responsive layout contract. |
| `tests/Frontend/ssr-render-smoke.mjs` | SSR regression fixture/assertions. |

### Task 1: Publish and render upload failures

**Files:** Modify `resources/js/components/uploader/Uploader.vue`, `resources/js/pages/settings/style/Edit.vue`; test `tests/Frontend/style-settings-page.test.ts`.

**Produces:** `upload-error: [message: string]`; all existing v-model and removal events remain unchanged.

- [ ] **Step 1: Write failing frontend assertions**

```ts
assert.match(source, /const uploadErrors = ref<Record<AssetField, string>>/);
assert.match(source, /@upload-error="\(message\) => \{ uploadErrors\[asset\.key\] = message \}"/);
assert.match(source, /uploadErrors\[asset\.key\] \?\? errors\[`\$\{asset\.key\}_upload_id`\]/);
```

- [ ] **Step 2: Confirm the assertion is red**

Run: `node --test tests/Frontend/style-settings-page.test.ts`

Expected: FAIL because neither error record nor event binding exists.

- [ ] **Step 3: Implement the smallest event contract**

```ts
type Emits = {
    'update:modelValue': [value: string[]];
    'update:removed': [value: Array<string | number>];
    updateTemporaryUploadIds: [value: string[]];
    updateRemovedExistingMediaIds: [value: Array<string | number>];
    'upload-error': [message: string];
};
```

For each FilePond `process` failure branch (local type, local size, non-2xx response, and XHR network error), calculate one message, invoke `emit('upload-error', message)`, then invoke FilePond `error(message)`. Emit an empty message immediately before `request.send(formData)` to clear a prior error. Do not move FilePond initialization from `onMounted`.

- [ ] **Step 4: Add the Style Settings consumer state**

```ts
const uploadErrors = ref<Record<AssetField, string>>({
    icon: '', icon_dark: '', logo: '', logo_dark: '', favicon: '', auth_split_background: '',
});
```

Bind `@upload-error="(message) => { uploadErrors[asset.key] = message }"` to the uploader and make its `InputError` prefer `uploadErrors[asset.key]` over `errors[`${asset.key}_upload_id`]`.

- [ ] **Step 5: Verify and commit**

Run: `node --test tests/Frontend/style-settings-page.test.ts && pnpm exec prettier --write resources/js/components/uploader/Uploader.vue resources/js/pages/settings/style/Edit.vue tests/Frontend/style-settings-page.test.ts`

Expected: PASS and formatted files. Commit: `fix: surface branding upload validation errors`.

### Task 2: Configure compact Style previews and ICO MIME variants

**Files:** Modify `resources/js/pages/settings/style/Edit.vue`, `tests/Feature/Settings/StyleSettingsUploadTest.php`, `tests/Frontend/style-settings-page.test.ts`.

**Produces:** 80px previews for Style Settings only; client MIME acceptance matching server MIME support.

- [ ] **Step 1: Add feature tests for file types**

```php
$this->actingAs($authorized)->postJson('/settings/style/uploads/favicon', [
    'file' => UploadedFile::fake()->create('favicon.ico', 64, 'image/x-icon'),
])->assertCreated();

$this->actingAs($authorized)->postJson('/settings/style/uploads/favicon', [
    'file' => UploadedFile::fake()->create('favicon.ico', 64, 'image/vnd.microsoft.icon'),
])->assertCreated();

$this->actingAs($authorized)->postJson('/settings/style/uploads/favicon', [
    'file' => UploadedFile::fake()->create('favicon.svg', 64, 'image/svg+xml'),
])->assertUnprocessable()->assertJsonValidationErrors('file');
```

- [ ] **Step 2: Verify the backend contract**

Run: `php artisan test tests/Feature/Settings/StyleSettingsUploadTest.php`

Expected: PASS; investigate any MIME failure before changing the UI.

- [ ] **Step 3: Make the client configuration exact**

```ts
accept: ['image/png', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'],
maxSize: 1024 * 1024,
```

Add `preview-size="compact"` to the Style Settings uploader only. Do not alter `Uploader.vue` default preview size or the existing Profile compact configuration.

- [ ] **Step 4: Add markup assertions and verify**

Assert both ICO MIME strings, `preview-size="compact"`, and the 1 MiB maximum in the Node test.

Run: `php artisan test tests/Feature/Settings/StyleSettingsUploadTest.php && node --test tests/Frontend/style-settings-page.test.ts`

Expected: PASS. Commit: `fix: accept ico favicons in style settings`.

### Task 3: Replace the favicon on Inertia navigation

**Files:** Modify `resources/js/pages/settings/style/Edit.vue`; test `tests/Frontend/style-settings-page.test.ts` and `tests/Frontend/ssr-render-smoke.mjs`.

**Produces:** One keyed `rel="icon"` head entry derived only from server-provided `branding.favicon`.

- [ ] **Step 1: Write failing markup assertion**

```ts
assert.match(source, /<link\s+head-key="favicon"\s+rel="icon"\s+:href="branding\.favicon"\s+sizes="any"\s*\/>/);
```

- [ ] **Step 2: Confirm red**

Run: `node --test tests/Frontend/style-settings-page.test.ts`

Expected: FAIL because the current page head has only a title.

- [ ] **Step 3: Implement declarative SSR-safe head markup**

```vue
<Head :title="trans('style.title')">
    <link head-key="favicon" rel="icon" :href="branding.favicon" sizes="any" />
</Head>
```

Leave the Blade favicon link unchanged for initial and no-JavaScript responses.

- [ ] **Step 4: Extend SSR fixture and run checks**

Add a Style Settings SSR fixture with a non-null `branding.favicon`, then assert the renderer completes and output contains the keyed/favicon URL. Run: `node --test tests/Frontend/style-settings-page.test.ts tests/Frontend/ssr-render-smoke.mjs`.

Expected: PASS without browser-global errors.

- [ ] **Step 5: Verify in the active local browser**

Save a local PNG replacement through the running `composer run dev` server and inspect `link[rel="icon"]` after the redirect. Expected: it uses the promoted media URL before a hot reload. Commit: `fix: refresh favicon after style settings save`.

### Task 4: Make Profile fallback and right-side avatar responsive

**Files:** Modify `resources/js/pages/Profile.vue`; create `tests/Frontend/profile-page.test.ts`; test `tests/Feature/Profile/AvatarUploadTest.php` and `tests/Frontend/ssr-render-smoke.mjs`.

**Produces:** One-column small layout and static large responsive two-column layout, with avatar on the right and initials always rendered when source is absent.

- [ ] **Step 1: Write a failing page contract test**

```ts
assert.doesNotMatch(source, /<div\s+v-if="props\.avatar"/);
assert.match(source, /<AvatarImage\s+v-if="props\.avatar"/);
assert.match(source, /<AvatarFallback>\s*\{\{\s*avatarInitials/);
assert.match(source, /grid-cols-1.*lg:grid-cols-\[minmax\(0,1fr\)_16rem\]/s);
assert.match(source, /lg:col-start-2/);
```

- [ ] **Step 2: Confirm red**

Run: `node --test tests/Frontend/profile-page.test.ts`

Expected: FAIL because the Avatar is currently guarded and no responsive form grid exists.

- [ ] **Step 3: Implement static responsive composition**

Wrap the form content after its hidden input in a static grid such as:

```vue
<div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_16rem]">
    <div class="space-y-6">...</div>
    <div class="grid gap-3 lg:col-start-2 lg:row-start-1">...</div>
</div>
```

Move the avatar label, Avatar, remove link, uploader, and avatar error into the right column. Place text fields and save button in the left column. Render the Avatar regardless of `props.avatar`:

```vue
<Avatar class="size-16">
    <AvatarImage v-if="props.avatar" :src="props.avatar.source" :alt="user.name" />
    <AvatarFallback>{{ avatarInitials }}</AvatarFallback>
</Avatar>
```

Keep the remove link conditional on `props.avatar`. Do not read viewport/browser globals.

- [ ] **Step 4: Add SSR coverage and verify**

Add a Profile SSR fixture with `avatar: null`; assert it renders initials/fallback structure without an exception. Run: `node --test tests/Frontend/profile-page.test.ts tests/Frontend/ssr-render-smoke.mjs && php artisan test tests/Feature/Profile/AvatarUploadTest.php`.

Expected: PASS.

- [ ] **Step 5: Manual responsive verification and commit**

At a large viewport, the avatar control is right of fields; at a small viewport it precedes fields in one column. Delete an existing avatar and confirm initials appear before reload. Commit: `fix: improve profile avatar layout`.

### Task 5: Finishing gate and independent review

**Files:** Only Task 1–4 files when a scoped test failure requires correction.

- [ ] **Step 1: Inspect integration diff**

Run: `git diff --check 0ce3e09d57b6e2eb09829c717802fbd697a2b3a7..HEAD && git status --short`

Expected: no whitespace failures or unrelated files.

- [ ] **Step 2: Run automated checks**

Run: `vendor/bin/pint --dirty --format agent && pnpm run lint && pnpm run format && composer run agent:gate`

Expected: PASS. Wayfinder generation is not required because no route/controller changes are planned.

- [ ] **Step 3: Obtain an independent review**

Review the FilePond event API, immediate error rendering, ICO MIME compatibility, keyed favicon behavior, SSR tests, conditional Avatar image/fallback, and both responsive breakpoints. Record browser verification and that no Serena memory update is needed because architecture did not change.
