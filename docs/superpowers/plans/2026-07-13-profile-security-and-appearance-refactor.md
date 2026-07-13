# Profile, Security, and Appearance Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Profile and Security independent account capabilities and replace the Appearance page with a reusable three-state theme selector in the application header.

**Architecture:** Profile and Security move from the Settings domain into dedicated route files, PHP namespaces, Inertia page names, and feature-test directories while retaining their named-route APIs. A reusable header component owns theme-selection UI and delegates all persistence and document-theme work to the existing `useAppearance` composable. No database or dependency changes are required.

**Tech Stack:** Laravel 13, PHP 8.5, Inertia v3, Vue 3, Wayfinder, shadcn-vue/Radix primitives, Tailwind CSS v4, Pest 4.

## Global Constraints

- URLs must be `/profile`, `/profile/avatar-uploads`, `/profile/avatar`, `/security`, and `/security/password`; do not retain `/settings/*` redirects.
- Preserve named routes `profile.*`, `security.edit`, and `user-password.update`, plus all existing middleware, validation, actions, flash messages, and response behavior.
- No affected backend class, frontend page, generated Wayfinder import, or feature-test directory may retain `Settings` ownership.
- Do not add dependencies, migrations, API endpoints, or server-side appearance storage.
- Appearance remains exactly `light`, `dark`, or `system`, persisted through the existing composable and presented with shadcn-vue `DropdownMenuRadioGroup` and `DropdownMenuRadioItem`.
- Use Wayfinder generated functions instead of hard-coded URLs in Vue code. Regenerate them after every route/controller move.
- Use Pest feature tests for HTTP/Inertia behavior; run Pint after PHP changes and run TypeScript, lint/format, build, and `composer ci:check` quality gates before handoff.

---

## Target File Structure

- `routes/profile.php` owns all authenticated profile and avatar endpoints.
- `routes/security.php` owns verified security, password update, and passkey well-known endpoints.
- `app/Http/Controllers/ProfileController.php` and `AvatarUploadController.php` own profile HTTP behavior; `app/Http/Requests/ProfileUpdateRequest.php` owns profile validation.
- `app/Http/Controllers/SecurityController.php` plus `app/Http/Requests/PasswordUpdateRequest.php` and `TwoFactorAuthenticationRequest.php` own security HTTP behavior.
- `resources/js/pages/Profile.vue` and `Security.vue` are standalone `AppLayout` pages.
- `resources/js/components/AppearanceToggle.vue` is the reusable three-state chooser rendered by `AppHeader.vue`.
- `tests/Feature/Profile/` owns Profile and avatar behavior; `tests/Feature/Security/` owns Security behavior.

### Task 1: Move Profile and avatar HTTP ownership out of Settings

**Files:**
- Create: `routes/profile.php`
- Create: `app/Http/Controllers/ProfileController.php`
- Create: `app/Http/Controllers/AvatarUploadController.php`
- Create: `app/Http/Requests/ProfileUpdateRequest.php`
- Modify: `routes/web.php`
- Delete: `app/Http/Controllers/Settings/ProfileController.php`
- Delete: `app/Http/Controllers/Settings/AvatarUploadController.php`
- Delete: `app/Http/Requests/Settings/ProfileUpdateRequest.php`
- Modify: `tests/Feature/Settings/ProfileUpdateTest.php`
- Modify: `tests/Feature/Settings/AvatarUploadTest.php`
- Create: `tests/Feature/Profile/ProfileUpdateTest.php`
- Create: `tests/Feature/Profile/AvatarUploadTest.php`

**Interfaces:**
- Consumes: `UpdateUserProfile::handle(User, array{name: string, email: string, phone: ?string}): User`, `PromoteTemporaryAvatarUpload::handle(User, ?string): void`, and the current `StoreTemporaryAvatarUploadRequest` contract.
- Produces: `profile.edit` (`GET /profile`), `profile.update` (`PATCH /profile`), `profile.avatar-uploads.store` (`POST /profile/avatar-uploads`), `profile.avatar-uploads.destroy` (`DELETE /profile/avatar-uploads/{temporaryAvatarUpload}`), and `profile.avatar.destroy` (`DELETE /profile/avatar`).

- [ ] **Step 1: Move the Profile tests and make their URL/component expectations fail**

```bash
mkdir -p tests/Feature/Profile
git mv tests/Feature/Settings/ProfileUpdateTest.php tests/Feature/Profile/ProfileUpdateTest.php
git mv tests/Feature/Settings/AvatarUploadTest.php tests/Feature/Profile/AvatarUploadTest.php
```

In `ProfileUpdateTest.php`, change the page assertion and add the direct-path regression:

```php
->get('/profile')
->assertInertia(fn (Assert $page) => $page->component('Profile'));

test('legacy settings profile URL is unavailable', function () {
    $this->actingAs(User::factory()->create())
        ->get('/settings/profile')
        ->assertNotFound();
});
```

In `AvatarUploadTest.php`, add one direct URL assertion for the staged upload endpoint:

```php
$this->actingAs($user)
    ->postJson('/profile/avatar-uploads', ['file' => UploadedFile::fake()->image('avatar.png')])
    ->assertCreated();
```

- [ ] **Step 2: Run the moved profile tests to verify the new contract fails**

Run: `php artisan test --compact tests/Feature/Profile/ProfileUpdateTest.php tests/Feature/Profile/AvatarUploadTest.php`

Expected: FAIL because `/profile` is not registered and the rendered Inertia component remains `settings/Profile`.

- [ ] **Step 3: Move classes and register the non-Settings routes**

```bash
git mv app/Http/Controllers/Settings/ProfileController.php app/Http/Controllers/ProfileController.php
git mv app/Http/Controllers/Settings/AvatarUploadController.php app/Http/Controllers/AvatarUploadController.php
git mv app/Http/Requests/Settings/ProfileUpdateRequest.php app/Http/Requests/ProfileUpdateRequest.php
```

Update namespaces/imports to remove `Settings`, change the rendered component to `Profile`, and create `routes/profile.php` with this route contract:

```php
Route::middleware(['auth'])->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('profile/avatar-uploads', [AvatarUploadController::class, 'store'])->name('profile.avatar-uploads.store');
    Route::delete('profile/avatar-uploads/{temporaryAvatarUpload}', [AvatarUploadController::class, 'destroy'])->name('profile.avatar-uploads.destroy');
    Route::delete('profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
});
```

Require `profile.php` from `routes/web.php` and remove every Profile/avatar route from the Settings route file.

- [ ] **Step 4: Run the Profile tests to verify behavior and ownership remain intact**

Run: `php artisan test --compact tests/Feature/Profile/ProfileUpdateTest.php tests/Feature/Profile/AvatarUploadTest.php`

Expected: PASS, including profile update, avatar staging, ownership, cleanup, and the new `/profile` / legacy-URL assertions.

- [ ] **Step 5: Format the moved PHP files**

Run: `vendor/bin/pint --dirty --format agent`

Expected: exits 0 and formats only changed PHP files.

- [ ] **Step 6: Commit the Profile domain move**

```bash
git add routes/web.php routes/profile.php app/Http/Controllers app/Http/Requests tests/Feature/Profile
git rm -r app/Http/Controllers/Settings app/Http/Requests/Settings tests/Feature/Settings
git commit -m "refactor: move profile out of settings"
```

Do not run the `git rm` command if Security files still exist in those directories; stage only the Profile deletions in this task.

### Task 2: Move Security HTTP ownership and retain Fortify protection

**Files:**
- Create: `routes/security.php`
- Create: `app/Http/Controllers/SecurityController.php`
- Create: `app/Http/Requests/PasswordUpdateRequest.php`
- Create: `app/Http/Requests/TwoFactorAuthenticationRequest.php`
- Modify: `routes/web.php`
- Delete: `app/Http/Controllers/Settings/SecurityController.php`
- Delete: `app/Http/Requests/Settings/PasswordUpdateRequest.php`
- Delete: `app/Http/Requests/Settings/TwoFactorAuthenticationRequest.php`
- Create: `tests/Feature/Security/SecurityTest.php`
- Delete: `tests/Feature/Settings/SecurityTest.php`

**Interfaces:**
- Consumes: `UpdateUserPassword::handle(User, string): void`, `Features::canManageTwoFactorAuthentication()`, `Features::canManagePasskeys()`, and `RequirePassword`.
- Produces: `security.edit` (`GET /security`, authenticated + verified + password-confirmed) and `user-password.update` (`PUT /security/password`, authenticated + verified + `throttle:6,1`). `well-known.passkeys` still returns URLs generated from `security.edit`.

- [ ] **Step 1: Move Security tests and require the new URL/component contract**

```bash
mkdir -p tests/Feature/Security
git mv tests/Feature/Settings/SecurityTest.php tests/Feature/Security/SecurityTest.php
```

Change the displayed-page request and assertion:

```php
->get('/security')
->assertInertia(fn (Assert $page) => $page->component('Security'));
```

Add the removed-path regression:

```php
test('legacy settings security URL is unavailable', function () {
    $this->actingAs(User::factory()->create())
        ->get('/settings/security')
        ->assertNotFound();
});
```

- [ ] **Step 2: Run Security tests to verify the target contract fails**

Run: `php artisan test --compact tests/Feature/Security/SecurityTest.php`

Expected: FAIL because `/security` does not exist and the page remains `settings/Security`.

- [ ] **Step 3: Move Security classes and define routes**

```bash
git mv app/Http/Controllers/Settings/SecurityController.php app/Http/Controllers/SecurityController.php
git mv app/Http/Requests/Settings/PasswordUpdateRequest.php app/Http/Requests/PasswordUpdateRequest.php
git mv app/Http/Requests/Settings/TwoFactorAuthenticationRequest.php app/Http/Requests/TwoFactorAuthenticationRequest.php
```

Change the three PHP namespaces/imports, render `Security`, and create `routes/security.php`:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('security/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');
});

Route::get('.well-known/passkey-endpoints', fn () => response()->json([
    'enroll' => route('security.edit'),
    'manage' => route('security.edit'),
]))->name('well-known.passkeys');
```

Require this route file from `routes/web.php`. Remove the old Settings route file only after Tasks 1 and 2 have moved every route from it.

- [ ] **Step 4: Verify Security behavior and route names**

Run: `php artisan test --compact tests/Feature/Security/SecurityTest.php && php artisan route:list --name=security --name=user-password --name=well-known.passkeys`

Expected: tests PASS; route list reports `/security`, `/security/password`, and `/.well-known/passkey-endpoints`, with the preserved names and middleware.

- [ ] **Step 5: Format and commit the Security move**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add routes/web.php routes/security.php app/Http/Controllers/SecurityController.php app/Http/Requests tests/Feature/Security
git rm routes/settings.php app/Http/Controllers/Settings/SecurityController.php app/Http/Requests/Settings/PasswordUpdateRequest.php app/Http/Requests/Settings/TwoFactorAuthenticationRequest.php tests/Feature/Settings/SecurityTest.php
git commit -m "refactor: move security out of settings"
```

### Task 3: Move Inertia pages and remove Settings navigation/layout

**Files:**
- Create: `resources/js/pages/Profile.vue`
- Create: `resources/js/pages/Security.vue`
- Modify: `resources/js/app.ts`
- Modify: `resources/js/components/UserMenuContent.vue`
- Delete: `resources/js/pages/settings/Profile.vue`
- Delete: `resources/js/pages/settings/Security.vue`
- Delete: `resources/js/layouts/settings/Layout.vue`

**Interfaces:**
- Consumes: Wayfinder named route imports `@/routes/profile`, `@/routes/security`, and controller imports regenerated in Task 5.
- Produces: standalone `Profile` and `Security` Inertia pages resolved to `AppLayout`; the user menu links to `profile.edit` with the label `Profile`.

- [ ] **Step 1: Move pages and update their expected Inertia component names first**

```bash
git mv resources/js/pages/settings/Profile.vue resources/js/pages/Profile.vue
git mv resources/js/pages/settings/Security.vue resources/js/pages/Security.vue
```

The controllers will now render `Profile` and `Security`; do not add page-level `layout` exports because `app.ts` owns the layout resolver.

- [ ] **Step 2: Remove Settings-specific layout resolution and user-menu wording**

Replace the settings branch in `resources/js/app.ts` so only auth pages get `AuthLayout` and every non-auth page gets `AppLayout`:

```ts
layout: (name) => {
    if (name.startsWith('auth/')) {
        return AuthLayout;
    }

    return AppLayout;
},
```

In `UserMenuContent.vue`, retain `:href="edit()"` but change the Lucide icon/import and visible label from Settings to Profile. Delete `layouts/settings/Layout.vue` after no page imports or component-name branches refer to it.

- [ ] **Step 3: Verify types fail before generated imports are refreshed**

Run: `pnpm types:check`

Expected: it may FAIL with missing generated controller imports until Task 5 regenerates Wayfinder; do not hand-edit `resources/js/actions` or `resources/js/routes`.

- [ ] **Step 4: Remove stale Settings page artifacts**

```bash
git rm resources/js/pages/settings/Profile.vue resources/js/pages/settings/Security.vue resources/js/layouts/settings/Layout.vue
rmdir resources/js/pages/settings resources/js/layouts/settings 2>/dev/null || true
```

- [ ] **Step 5: Commit the standalone-page transition**

```bash
git add resources/js/app.ts resources/js/pages/Profile.vue resources/js/pages/Security.vue resources/js/components/UserMenuContent.vue
git rm -r resources/js/pages/settings resources/js/layouts/settings
git commit -m "refactor: make profile and security standalone pages"
```

### Task 4: Replace Appearance page with a reusable header selector

**Files:**
- Create: `resources/js/components/AppearanceToggle.vue`
- Modify: `resources/js/components/AppHeader.vue`
- Delete: `resources/js/components/AppearanceTabs.vue`
- Delete: `resources/js/pages/settings/Appearance.vue`
- Modify: `resources/js/types/ui.ts` only if `Appearance`/`ResolvedAppearance` are no longer exported from the current shared location; otherwise leave it unchanged.

**Interfaces:**
- Consumes: `useAppearance(): { appearance: Ref<Appearance>; resolvedAppearance: ComputedRef<ResolvedAppearance>; updateAppearance(value: Appearance): void }`.
- Produces: `<AppearanceToggle />`, a header-safe component whose radio-group `model-value` is `appearance` and whose update handler accepts exactly `Appearance` (`'light' | 'dark' | 'system'`).

- [ ] **Step 1: Add the selector component with a failing type contract**

Create `AppearanceToggle.vue` with typed choices and an update handler:

```ts
const { appearance, updateAppearance } = useAppearance();

const appearances = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
] as const;

function selectAppearance(value: Appearance): void {
    updateAppearance(value);
}
```

Render it with the existing shadcn-vue primitives:

```vue
<DropdownMenu>
    <DropdownMenuTrigger :as-child="true">
        <Button variant="ghost" size="icon" aria-label="Change appearance">
            <Sun v-if="appearance === 'light'" class="size-5" />
            <Moon v-else-if="appearance === 'dark'" class="size-5" />
            <Monitor v-else class="size-5" />
        </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end">
        <DropdownMenuRadioGroup :model-value="appearance" @update:model-value="selectAppearance">
            <DropdownMenuRadioItem v-for="item in appearances" :key="item.value" :value="item.value">
                <component :is="item.icon" class="mr-2 size-4" />{{ item.label }}
            </DropdownMenuRadioItem>
        </DropdownMenuRadioGroup>
    </DropdownMenuContent>
</DropdownMenu>
```

- [ ] **Step 2: Run TypeScript checking to catch incorrect radio-group value typing**

Run: `pnpm types:check`

Expected: FAIL only if the menu emits `string` rather than `Appearance`; narrow the emitted value before passing it to `selectAppearance`, without weakening `selectAppearance` to `string`.

- [ ] **Step 3: Integrate the selector in the persistent header**

Import `AppearanceToggle` in `AppHeader.vue` and render it in the right-side action group before the user avatar dropdown:

```vue
<div class="relative flex items-center space-x-1">
    <AppearanceToggle />
    <Button variant="ghost" size="icon" class="group h-9 w-9 cursor-pointer">
        <Search class="size-5 opacity-80 group-hover:opacity-100" />
    </Button>
</div>
```

This preserves the existing header layout on desktop and mobile and exposes the theme menu on every page using `AppLayout`.

- [ ] **Step 4: Delete page-only Appearance artifacts and verify no stale references remain**

```bash
git rm resources/js/components/AppearanceTabs.vue resources/js/pages/settings/Appearance.vue
rg -n 'AppearanceTabs|settings/Appearance|appearance\.edit|settings/appearance|layouts/settings|pages/settings|Controllers/Settings|Requests/Settings|Feature/Settings' app resources routes tests
```

Expected: `rg` exits 1 with no matches for all removed Settings/Appearance references.

- [ ] **Step 5: Format frontend files and commit**

Run: `pnpm format`

```bash
git add resources/js/components/AppearanceToggle.vue resources/js/components/AppHeader.vue resources/js
git commit -m "refactor: replace appearance page with header toggle"
```

### Task 5: Regenerate contracts and perform end-to-end verification

**Files:**
- Modify (generated): `resources/js/actions/App/Http/Controllers/ProfileController.ts`
- Modify (generated): `resources/js/actions/App/Http/Controllers/AvatarUploadController.ts`
- Modify (generated): `resources/js/actions/App/Http/Controllers/SecurityController.ts`
- Modify (generated): `resources/js/routes/profile/index.ts`
- Modify (generated): `resources/js/routes/security/index.ts`
- Delete (generated): `resources/js/actions/App/Http/Controllers/Settings/*`
- Delete (generated): `resources/js/routes/appearance/index.ts`

**Interfaces:**
- Consumes: final Laravel routes and controller namespaces from Tasks 1–2.
- Produces: generated typed imports resolving Profile, AvatarUpload, and Security controller actions plus URLs that no longer contain `/settings`.

- [ ] **Step 1: Regenerate Wayfinder output from final routes**

Run: `php artisan wayfinder:generate --no-interaction`

Expected: generated controller definitions live outside `Controllers/Settings`; `profile.edit().url` is `/profile`, `security.edit().url` is `/security`, and no `appearance` route module remains.

- [ ] **Step 2: Run focused backend regression tests**

Run: `php artisan test --compact tests/Feature/Profile/ProfileUpdateTest.php tests/Feature/Profile/AvatarUploadTest.php tests/Feature/Security/SecurityTest.php`

Expected: PASS with all existing profile, avatar, password, two-factor, passkey, middleware, and new removed-path behavior intact.

- [ ] **Step 3: Verify route table and removed Settings namespace**

Run:

```bash
php artisan route:list --path=profile
php artisan route:list --path=security
php artisan route:list --path=settings
rg -n 'Settings|settings/' app resources routes tests --glob '!resources/js/components/ui/**'
```

Expected: the first two commands list only new paths; the settings route list is empty; the final search returns no affected-domain ownership references. If unrelated settings text exists outside these domains, remove only references covered by this plan.

- [ ] **Step 4: Run quality gates**

Run:

```bash
vendor/bin/pint --dirty --format agent
pnpm format:check
pnpm lint:check
pnpm types:check
pnpm build
composer ci:check
```

Expected: all commands exit 0. The build also confirms Vite can consume regenerated Wayfinder modules, and `composer ci:check` verifies the repository's complete Composer-defined CI contract.

- [ ] **Step 5: Inspect the final diff and commit**

Run: `git diff --check && git status --short`

Expected: no whitespace errors and only intended Profile, Security, AppearanceToggle, route, generated-code, and test changes.

```bash
git add app routes resources/js tests
git commit -m "refactor: remove settings account domain"
```

## Plan Self-Review

- Spec coverage: Tasks 1–3 move all Profile/Security route, backend, page, layout, and test ownership; Task 4 replaces Appearance with the approved three-state shadcn-vue control; Task 5 verifies generated contracts and quality gates.
- Placeholder scan: no deferred work or unspecified implementation steps remain; route names, paths, namespace destinations, component contracts, and commands are explicit.
- Type consistency: `Appearance` remains the union used by `useAppearance`, `AppearanceToggle`, and the radio menu; preserved named routes remain the only frontend navigation contract.
