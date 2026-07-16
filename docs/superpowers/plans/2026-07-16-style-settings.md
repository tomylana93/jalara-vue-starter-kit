# Style Settings Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use
> `superpowers:subagent-driven-development` (recommended) or
> `superpowers:executing-plans` to implement this plan task-by-task. Steps use
> checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add administrator-managed Style Settings, Media Library branding, a
split-auth background, and queued image conversions for branding and profile
avatars.

**Architecture:** Scalar presentation choices live in Spatie `StyleSettings`.
A singleton `SiteBranding` Eloquent model owns six single-file Media Library
collections. A purpose-aware `TemporaryUpload` pipeline stages originals for
both profile and branding flows; resource-specific requests enforce ownership,
authorization, and file rules. Save actions promote originals atomically and
Spatie dispatches conversions after commit; resolvers use conversion, original,
then official fallback precedence.

**Tech Stack:** PHP 8.5, Laravel 13, Spatie Laravel Settings 3, Spatie Media
Library 11, Spatie Image, Laravel queues, Pest 4, Inertia 3, Vue 3, TypeScript,
Wayfinder, FilePond, Tailwind CSS 4, Vite 8, Bunny Fonts build integration.

## Global Constraints

- Follow the approved design in
  `docs/superpowers/specs/2026-07-16-style-settings-design.md`.
- Do not implement branding fallbacks until the official files referenced by
  `/home/tomylana93/projects/jalara/brand/brand-guidelines.md` exist.
- Never copy Nova placeholder assets or recreate the Jalara logo.
- Keep `useAppearance` light/dark/system independent from the site palette.
- Use `manage settings` for all Style Settings authorization.
- Stage originals synchronously; run every derived image conversion in a queue.
- Use conversion → original → official fallback resolution.
- Preserve avatar conversion name `avatar`, WebP 256×256, `Fit::Crop`.
- Use Wayfinder route/action imports; never hard-code application URLs.
- Mirror presentation copy in `lang/en` and `lang/id` and regenerate types.
- One writer owns routes, migrations, generated Wayfinder output, and shared
  frontend types during implementation.

---

## File Structure

| Area | Responsibility |
| --- | --- |
| `app/Enums/Site*.php` | Typed Style Settings values and frontend options |
| `app/Settings/StyleSettings.php` | Scalar settings only |
| `app/Models/SiteBranding.php` | Singleton owner of final branding collections/conversions |
| `app/Models/TemporaryUpload.php` | Generic owned, purpose-bound staging metadata |
| `app/Enums/TemporaryUploadPurpose.php` | Prevent cross-purpose promotion |
| `app/Support/PublicMediaDisk.php` | Deterministic R2/public selection |
| `app/Support/Branding/BrandingResolver.php` | Conversion/original/fallback public contract |
| `app/Actions/Uploads/**` | Stage, cancel, validate, promote, and prune staging files |
| `app/Actions/Settings/UpdateStyleSettings.php` | Atomic scalar/media orchestration and compensation |
| `app/Http/**/Style*` | Authorized Style Settings HTTP boundary |
| `resources/js/types/settings.ts` | Shared scalar and branding contracts |
| `resources/js/components/AppBrand.vue` | Single branding-selection consumer |
| `resources/js/composables/useStyleSettings.ts` | Runtime palette/font attributes |
| `resources/js/pages/settings/style/Edit.vue` | Local-preview Style Settings editor |

### Task 1: Establish the scalar settings and permanent media owner

**Files:**

- Create: `app/Enums/SiteLogoStyle.php`
- Create: `app/Enums/SiteAuthLayout.php`
- Create: `app/Enums/SiteLayout.php`
- Create: `app/Enums/SiteTheme.php`
- Create: `app/Enums/SiteFont.php`
- Create: `app/Settings/StyleSettings.php`
- Create: `database/settings/2026_07_16_000001_create_style_settings.php`
- Create: `app/Models/SiteBranding.php`
- Create: `database/migrations/2026_07_16_000002_create_site_brandings_table.php`
- Create: `app/Support/PublicMediaDisk.php`
- Modify: `app/Support/MediaDisk.php`
- Test: `tests/Feature/Settings/StyleSettingsTest.php`
- Test: `tests/Unit/Support/PublicMediaDiskTest.php`
- Test: `tests/Unit/Models/SiteBrandingTest.php`
- Modify: `tests/Feature/Settings/SettingsMigrationRollbackTest.php`

**Interfaces:**

- Produces `StyleSettings` properties `site_logo_style`, `site_auth_layout`,
  `site_layout`, `site_theme`, and `site_font`.
- Produces `SiteBranding::singleton(): SiteBranding` and collection constants
  `icon`, `icon_dark`, `logo`, `logo_dark`, `favicon`, and
  `auth_split_background`.
- Produces `PublicMediaDisk::name(): string`, used by both avatar and branding.

- [ ] **Step 1: Write failing enum/default and migration tests.**

```php
test('style settings use the approved defaults', function () {
    $settings = app(StyleSettings::class);

    expect($settings->site_logo_style)->toBe('icon')
        ->and($settings->site_auth_layout)->toBe('simple')
        ->and($settings->site_layout)->toBe('sidebar')
        ->and($settings->site_theme)->toBe('zinc')
        ->and($settings->site_font)->toBe('inter');
});

test('style enums expose the complete Nova-compatible matrix', function () {
    expect(SiteTheme::values())->toBe([
        'zinc', 'slate', 'emerald', 'rose', 'indigo',
        'violet', 'cyan', 'orange', 'teal', 'fuchsia',
    ])->and(SiteAuthLayout::values())->toBe(['simple', 'split', 'card']);
});
```

- [ ] **Step 2: Run the focused tests and confirm missing classes fail.**

Run:

```bash
php artisan test --compact tests/Feature/Settings/StyleSettingsTest.php \
  tests/Feature/Settings/SettingsMigrationRollbackTest.php
```

Expected: FAIL because `StyleSettings` and its enums do not exist.

- [ ] **Step 3: Implement the enums, settings class, and settings migration.**

Each enum is string-backed, uses the existing `HasOptions` concern, and exposes
translation keys rather than presentation literals. The migration adds exactly:

```php
$migrator->add('style.site_logo_style', SiteLogoStyle::Icon->value);
$migrator->add('style.site_auth_layout', SiteAuthLayout::Simple->value);
$migrator->add('style.site_layout', SiteLayout::Sidebar->value);
$migrator->add('style.site_theme', SiteTheme::Zinc->value);
$migrator->add('style.site_font', SiteFont::Inter->value);
```

- [ ] **Step 4: Write failing disk and Media Library collection tests.**

Assert `PublicMediaDisk::name()` returns `public` for incomplete R2 and `r2`
only when key, secret, bucket, endpoint, and public URL exist. Assert
`SiteBranding::singleton()` is stable and every collection is `singleFile`, uses
the selected disk, and accepts only its approved MIME types.

- [ ] **Step 5: Implement `PublicMediaDisk` and `SiteBranding`.**

`SiteBranding` implements `HasMedia`, uses `InteractsWithMedia`, has no user-editable
attributes, and resolves one persisted row through:

```php
public static function singleton(): self
{
    return self::query()->firstOrCreate(['key' => 'site']);
}
```

Register queued conversions with these exact contracts:

```php
$this->addMediaConversion('icon_web')
    ->performOnCollections('icon', 'icon_dark')
    ->format('webp')->fit(Fit::Max, 512, 512)->queued();

$this->addMediaConversion('logo_web')
    ->performOnCollections('logo', 'logo_dark')
    ->format('webp')->fit(Fit::Max, 1600, 600)->queued();

$this->addMediaConversion('auth_background_web')
    ->performOnCollections('auth_split_background')
    ->format('webp')->quality(85)->fit(Fit::Max, 1920, 1920)->queued();
```

Do not register a favicon conversion. Update `MediaDisk::avatar()` to delegate
to `PublicMediaDisk::name()` so both features share disk selection.

- [ ] **Step 6: Run focused tests.**

Run:

```bash
php artisan test --compact tests/Feature/Settings/StyleSettingsTest.php \
  tests/Feature/Settings/SettingsMigrationRollbackTest.php \
  tests/Unit/Support/PublicMediaDiskTest.php \
  tests/Unit/Models/SiteBrandingTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit Task 1.**

```bash
git add app/Enums app/Settings/StyleSettings.php app/Models/SiteBranding.php \
  app/Support database tests/Feature/Settings tests/Unit
git commit -m "feat: add style settings media foundation"
```

### Task 2: Replace avatar-only staging with a purpose-aware upload pipeline

**Files:**

- Create: `app/Enums/TemporaryUploadPurpose.php`
- Create: `app/Data/Uploads/UploadConstraint.php`
- Create: `app/Models/TemporaryUpload.php`
- Create: `database/migrations/2026_07_16_000003_create_temporary_uploads_table.php`
- Create: `database/factories/TemporaryUploadFactory.php`
- Create: `app/Actions/Uploads/StageTemporaryUpload.php`
- Create: `app/Actions/Uploads/DeleteTemporaryUpload.php`
- Create: `app/Actions/Uploads/ValidateTemporaryUpload.php`
- Create: `app/Actions/Uploads/PromoteTemporaryUpload.php`
- Create: `app/Actions/Uploads/PruneTemporaryUploads.php`
- Create: `app/Console/Commands/PruneTemporaryUploads.php`
- Modify: `app/Http/Controllers/Profile/AvatarUploadController.php`
- Modify: `app/Http/Requests/Profile/StoreTemporaryAvatarUploadRequest.php`
- Modify: `app/Actions/PromoteTemporaryAvatarUpload.php`
- Modify: `routes/profile.php`
- Modify: `routes/console.php`
- Delete after migration: `app/Models/TemporaryAvatarUpload.php`
- Delete after migration: `database/factories/TemporaryAvatarUploadFactory.php`
- Delete after migration: `app/Actions/Profile/StageTemporaryAvatarUpload.php`
- Delete after migration: `app/Actions/Profile/DeleteTemporaryAvatarUpload.php`
- Delete after migration: `app/Actions/Profile/PurgeExpiredAvatarUploads.php`
- Delete after migration: `app/Console/Commands/PurgeExpiredAvatarUploads.php`
- Replace tests: `tests/Feature/Profile/AvatarUploadTest.php`
- Create: `tests/Feature/Uploads/TemporaryUploadTest.php`
- Create: `tests/Feature/Actions/Uploads/PruneTemporaryUploadsTest.php`
- Modify: `tests/Feature/PurgeExpiredAvatarUploadsCommandTest.php`

**Interfaces:**

- `StageTemporaryUpload::handle(User $owner, UploadedFile $file,
  TemporaryUploadPurpose $purpose): TemporaryUpload`.
- `UploadConstraint` is an immutable value object containing allowed MIME
  strings, maximum bytes, and optional minimum width/height.
- `ValidateTemporaryUpload::handle(User $owner, string $id,
  TemporaryUploadPurpose $purpose, UploadConstraint $constraint): TemporaryUpload`.
- `PromoteTemporaryUpload::handle(TemporaryUpload $upload, HasMedia $owner,
  string $collection): Media`.
- Profile route parameter becomes `{temporaryUpload}` and is always constrained
  to purpose `avatar` in the controller/action.

- [ ] **Step 1: Write failing ownership, purpose, expiry, cancellation, and prune tests.**

Cover same-owner success; cross-owner 403; avatar ID rejected by branding flow;
expired/missing-file rejection; repeated cancellation; and pruning records older
than 24 hours.

- [ ] **Step 2: Run the new upload tests and verify failure.**

```bash
php artisan test --compact tests/Feature/Uploads \
  tests/Feature/Profile/AvatarUploadTest.php \
  tests/Feature/PurgeExpiredAvatarUploadsCommandTest.php
```

Expected: FAIL because the generic model/actions do not exist.

- [ ] **Step 3: Implement the generic schema and model.**

Use a UUID primary key, foreign UUID `user_id`, backed-enum purpose, disk, path,
original name, MIME, size, and `expires_at`. The model's deleting hook removes
the physical object idempotently and throws only when the disk reports a real
delete failure.

- [ ] **Step 4: Implement shared actions with resource-specific constraints.**

Keep accepted MIME, maximum bytes, and dimensions in immutable constraint
objects passed by Profile or Style requests. `ValidateTemporaryUpload` re-sniffs
MIME from file bytes and uses image dimensions from trusted content. Never let a
client-provided purpose select weaker rules.

- [ ] **Step 5: Migrate Profile to the generic pipeline.**

Keep the public JSON response `{id,name,size,type}` and FilePond behavior stable.
Update route-model binding and tests. Remove the avatar-specific staging classes
only after every reference search is clean.

- [ ] **Step 6: Replace the scheduled command.**

Schedule `uploads:prune-temporary --hours=24` hourly with
`withoutOverlapping()`. Update the command test to assert all purposes are
pruned, not only avatar.

- [ ] **Step 7: Verify references and focused tests.**

Run:

```bash
rg -n "TemporaryAvatarUpload|PurgeExpiredAvatarUploads|temporaryAvatarUpload" \
  app routes tests
php artisan test --compact tests/Feature/Uploads \
  tests/Feature/Profile/AvatarUploadTest.php \
  tests/Feature/PurgeExpiredAvatarUploadsCommandTest.php
```

Expected: no obsolete production references; PASS.

- [ ] **Step 8: Commit Task 2.**

```bash
git add app database routes tests
git commit -m "refactor: unify temporary image uploads"
```

### Task 3: Queue avatar conversion and implement atomic branding updates

**Files:**

- Modify: `app/Models/User.php`
- Modify: `app/Actions/PromoteTemporaryAvatarUpload.php`
- Modify: `app/Models/SiteBranding.php`
- Create: `app/Actions/Settings/UpdateStyleSettings.php`
- Create: `app/Data/Settings/StyleSettingsPayload.php`
- Create: `app/Support/Branding/BrandingResolver.php`
- Test: `tests/Feature/Settings/UpdateStyleSettingsTest.php`
- Test: `tests/Feature/Settings/BrandingResolverTest.php`
- Modify: `tests/Feature/Profile/AvatarUploadTest.php`
- Modify: `tests/Unit/Models/UserTest.php`

**Interfaces:**

- `UpdateStyleSettings::handle(StyleSettingsPayload $payload, User $actor): void`.
- `BrandingResolver::resolve(SiteBranding $branding): array` returns typed keys
  `icon`, `icon_dark`, `logo`, `logo_dark`, `favicon`, and
  `auth_split_background`, each URL or fallback URL as applicable.
- `User::avatarUrl()` returns generated `avatar` conversion when ready and the
  original URL while pending/failed.

- [ ] **Step 1: Write failing queue and resolver-precedence tests.**

Use `Queue::fake()` to assert conversions are queued. Create Media records with
and without generated conversions and assert conversion → original → fallback.
Assert favicon always resolves the original.

- [ ] **Step 2: Change avatar conversion from `nonQueued()` to `queued()`.**

Retain exactly:

```php
$this->addMediaConversion('avatar')
    ->performOnCollections('avatar')
    ->format('webp')
    ->fit(Fit::Crop, 256, 256)
    ->queued();
```

Update `avatarUrl()` to call `hasGeneratedConversion('avatar')` before choosing
the conversion URL.

- [ ] **Step 3: Write failing atomic replacement and compensation tests.**

Test scalar-only save, six collection promotions, independent removals,
background removal retaining `split`, old media preserved until replacement,
and a forced third-promotion exception leaving all settings/old media unchanged
and deleting newly created media.

- [ ] **Step 4: Implement `StyleSettingsPayload` and the update action.**

The payload uses exact enum values, nullable staging IDs for all six fields, and
six boolean removal flags. The action validates every staged file first, records
new media IDs for compensation, performs database writes in a transaction,
deletes superseded media only after replacements exist, and dispatches
conversion jobs after commit. Do not use `singleFile()` replacement semantics
until compensation has enough state to restore the old asset.

- [ ] **Step 5: Implement `BrandingResolver`.**

Use Media Library URLs and `hasGeneratedConversion()`. Map collection-specific
conversion names. Use official static fallback paths for the five branding
assets and return `null` for an absent split background.

- [ ] **Step 6: Run focused tests.**

```bash
php artisan test --compact tests/Feature/Settings/UpdateStyleSettingsTest.php \
  tests/Feature/Settings/BrandingResolverTest.php \
  tests/Feature/Profile/AvatarUploadTest.php tests/Unit/Models/UserTest.php
```

Expected: PASS and queue assertions prove no conversion runs in the request.

- [ ] **Step 7: Commit Task 3.**

```bash
git add app tests
git commit -m "feat: process application images on the queue"
```

### Task 4: Add the authorized Style Settings HTTP and shared-prop boundary

**Files:**

- Create: `app/Policies/StyleSettingsPolicy.php`
- Create: `app/Http/Requests/Settings/UpdateStyleSettingsRequest.php`
- Create: `app/Http/Requests/Settings/StoreBrandingUploadRequest.php`
- Create: `app/Http/Controllers/Settings/StyleSettingsController.php`
- Create: `app/Http/Controllers/Settings/BrandingUploadController.php`
- Modify: `routes/settings.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `app/Http/Middleware/HandleAppearance.php`
- Modify: `resources/views/app.blade.php`
- Create: `tests/Feature/Settings/StyleSettingsAccessTest.php`
- Create: `tests/Feature/Settings/StyleSettingsUploadTest.php`
- Create: `tests/Feature/Settings/StyleSettingsSharedPropsTest.php`
- Modify: `tests/Feature/Localization/SharedLocaleTest.php`

**Interfaces:**

- Named routes: `settings.style.edit`, `settings.style.update`,
  `settings.style.uploads.store`, `settings.style.uploads.destroy`.
- Edit props: `styleSettings`, enum option arrays, `branding`, and six
  FilePond-shaped existing-file payloads.
- Shared props: `style` scalar values and resolved `branding` URLs.

- [ ] **Step 1: Write failing access and upload matrix tests.**

Use guest, inactive, ordinary authenticated, and `manage settings` users. Assert
every Style route follows the same authorization boundary. Test PNG/JPEG/WebP
2 MiB icon/logo rules, PNG/WebP/ICO 1 MiB favicon rules, JPEG/WebP 5 MiB and
minimum 1200×800 background rules, SVG rejection, MIME spoofing, throttling,
owner mismatch, and purpose mismatch.

- [ ] **Step 2: Add policy, requests, controllers, and routes.**

Extend the existing `auth`, `verified`, active-user, precognitive, and throttle
patterns. Route authorization must call `view`/`update` on `StyleSettings`.
Branding upload requests map an enum-like field name to a fixed constraint; the
request body cannot provide arbitrary purpose or MIME rules.

- [ ] **Step 3: Test and implement shared props and first-paint data.**

`HandleInertiaRequests` shares one `style` object and one `branding` object.
`HandleAppearance` supplies Blade with site name, palette, font key, favicon,
and initial branding. Escape all attribute values and keep the existing
appearance bootstrap script.

- [ ] **Step 4: Generate Wayfinder and run focused tests.**

```bash
php artisan wayfinder:generate --with-form --no-interaction
php artisan test --compact tests/Feature/Settings/StyleSettingsAccessTest.php \
  tests/Feature/Settings/StyleSettingsUploadTest.php \
  tests/Feature/Settings/StyleSettingsSharedPropsTest.php
```

Expected: generated `.form()` helpers exist and tests PASS.

- [ ] **Step 5: Commit Task 4.**

```bash
git add app routes resources/views tests
git commit -m "feat: expose authorized style settings"
```

### Task 5: Apply branding, layout, palette, and self-hosted fonts

**Files:**

- Modify: `vite.config.ts`
- Modify: `resources/css/app.css`
- Modify: `resources/js/types/settings.ts`
- Modify: `resources/js/types/auth.ts`
- Create: `resources/js/composables/useStyleSettings.ts`
- Create: `resources/js/components/AppBrand.vue`
- Modify: `resources/js/components/AppLogo.vue`
- Modify: `resources/js/components/AppHeader.vue`
- Modify: `resources/js/layouts/AppLayout.vue`
- Modify: `resources/js/layouts/AuthLayout.vue`
- Modify: `resources/js/layouts/auth/AuthSimpleLayout.vue`
- Modify: `resources/js/layouts/auth/AuthCardLayout.vue`
- Modify: `resources/js/layouts/auth/AuthSplitLayout.vue`
- Modify: `resources/js/app.ts`
- Create: `tests/Frontend/style-settings.test.ts`
- Modify: `tests/Feature/Localization/ShellLocalizationTest.php`

**Interfaces:**

- `SharedStyleSettings` contains five scalar string unions.
- `BrandingAssets` contains the six resolved nullable/string URLs.
- `useStyleSettings()` watches Inertia props and sets `data-theme` and
  `data-font`; it never changes the user's appearance preference.
- `AppBrand` is the only component selecting logo style and light/dark variant.

- [ ] **Step 1: Write failing frontend contract tests.**

Test all ten palette selectors, all five `data-font` selectors, layout component
selection, `AppBrand` precedence, and split-background URL/null behavior. Add a
source architecture assertion that shell consumers use `AppBrand` rather than
selecting branding URLs independently.

- [ ] **Step 2: Bundle every approved font family and weight.**

Extend the existing Bunny Fonts Vite plugin configuration so Inter, Sora, Plus
Jakarta Sans, DM Sans, Space Grotesk, and Nunito are downloaded at build time.
Do not add runtime `<link>` insertion. Map the approved five pairings under
`html[data-font='…']`.

- [ ] **Step 3: Port Nova palettes and implement runtime style application.**

Add light and dark CSS tokens for zinc, slate, emerald, rose, indigo, violet,
cyan, orange, teal, and fuchsia. Initialize `useStyleSettings()` in `app.ts`
without removing `initializeTheme()`.

- [ ] **Step 4: Implement layout selection and the single branding consumer.**

Use computed dynamic components in `AppLayout` and `AuthLayout`. Replace every
shell/auth direct logo with `AppBrand`. In split auth render:

```vue
<div
    v-if="branding.auth_split_background"
    class="absolute inset-0 bg-cover bg-center"
    :style="{ backgroundImage: `url(${branding.auth_split_background})` }"
/>
<div class="absolute inset-0 bg-zinc-900/65" />
```

Keep the entire image panel hidden below `lg` and use solid zinc when the URL is
null.

- [ ] **Step 5: Run frontend and shell tests.**

```bash
pnpm exec vitest run tests/Frontend/style-settings.test.ts
php artisan test --compact tests/Feature/Localization/ShellLocalizationTest.php
pnpm run types:check
```

Expected: PASS with no runtime font-provider URL in built application code.

- [ ] **Step 6: Commit Task 5.**

```bash
git add vite.config.ts resources tests
git commit -m "feat: apply site branding and visual styles"
```

### Task 6: Build the Style Settings editor, localization, and finish the feature

**Files:**

- Create: `resources/js/pages/settings/style/Edit.vue`
- Modify: `resources/js/pages/settings/Index.vue`
- Modify: `resources/js/components/uploader/Uploader.vue` only if the existing
  generic API cannot represent the six single-file states
- Create: `lang/en/style.php`
- Create: `lang/id/style.php`
- Modify: `lang/en/settings.php`
- Modify: `lang/id/settings.php`
- Regenerate: `resources/js/types/translation.generated.ts`
- Create: `tests/Feature/Settings/StyleSettingsPageTest.php`
- Modify: `tests/Feature/Localization/PresentationCopyArchitectureTest.php`
- Modify: `tests/Frontend/translation.test.ts`
- Modify: `tests/Frontend/no-untranslated-copy.test.ts`
- Copy when available: official Jalara fallback assets into
  `public/assets/images/branding/`

**Interfaces:**

- The form submits five scalar values, six nullable staging IDs, and six
  explicit removal booleans through `settings.style.update.form()`.
- Each FilePond instance receives Wayfinder-generated upload and delete URLs,
  accepted MIME types, byte limit, existing-file payload, and localized labels.

- [ ] **Step 1: Gate on official fallback assets.**

Verify the sibling `jalara` repository contains the official primary logo,
dark-mode logo, square/icon asset, and dedicated favicon. Copy exact originals
without editing them. If they remain absent, stop and report the dependency;
do not substitute Nova or generated assets.

- [ ] **Step 2: Write failing page, navigation, localization, and payload tests.**

Assert Settings Index links to Style, edit props contain all option matrices and
existing files, the form uses generated Wayfinder helpers, all six upload states
transform to the exact backend fields, and every English key exists in
Indonesian.

- [ ] **Step 3: Implement the editor with local-only previews.**

Follow `resources/js/pages/settings/general/Edit.vue`. Use radio cards for logo,
app layout, and auth layout; selects or preview cards for theme/font; and six
single-file uploaders. Keep the background uploader visible for every auth
layout. Do not call `useStyleSettings()` with unsaved form state.

- [ ] **Step 4: Add localized copy and regenerate translation types.**

```bash
php artisan lang:export
```

Expected: `resources/js/types/translation.generated.ts` includes all new
`style.*` and Settings index keys, with parity tests passing.

- [ ] **Step 5: Run targeted feature/frontend tests.**

```bash
php artisan test --compact tests/Feature/Settings \
  tests/Feature/Profile/AvatarUploadTest.php \
  tests/Feature/Localization
pnpm exec vitest run tests/Frontend/style-settings.test.ts \
  tests/Frontend/translation.test.ts tests/Frontend/no-untranslated-copy.test.ts
```

Expected: PASS.

- [ ] **Step 6: Run the canonical finishing gate.**

```bash
php artisan wayfinder:generate --with-form --no-interaction
vendor/bin/pint --dirty --format agent
pnpm run lint
pnpm run format
composer run agent:gate
git diff --check
git status --short
```

Expected: every command passes; only files in this plan plus official fallback
assets are changed.

- [ ] **Step 7: Perform independent review and resolve findings.**

The reviewer must not be the writer. Review against the spec, with special
attention to authorization, cross-purpose staging, atomic compensation, queued
conversions, first-paint behavior, and fallback provenance. Resolve every P0–P2
finding and document any accepted P3.

- [ ] **Step 8: Commit Task 6.**

```bash
git add app database lang public/assets/images/branding resources routes tests \
  vite.config.ts
git commit -m "feat: add style settings editor"
```

## Plan Self-Review Matrix

| Spec requirement | Implemented by |
| --- | --- |
| Nova-compatible enums/defaults | Task 1 |
| Singleton Media Library branding owner | Task 1 |
| Generic owner/purpose-bound staging | Task 2 |
| 24-hour cancellation/pruning lifecycle | Task 2 |
| Queued avatar and branding conversions | Task 3 |
| Atomic replacement and compensation | Task 3 |
| Conversion/original/fallback resolver | Task 3 |
| `manage settings` HTTP boundary | Task 4 |
| Shared Inertia and first-paint Blade data | Task 4 |
| Self-hosted fonts, themes, layouts, AppBrand | Task 5 |
| Split background cover/overlay/zinc fallback | Task 5 |
| Local-preview editor and six uploaders | Task 6 |
| Official Jalara fallback dependency | Task 6 |
| English/Indonesian localization | Task 6 |
| Independent review and full gate | Task 6 |
