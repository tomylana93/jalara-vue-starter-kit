# General Settings Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a permission-protected General Settings area at `/settings/` and `/settings/general` for the global application name, description, and `en`/`id` locale.

**Architecture:** Spatie Laravel Settings persists a typed `general` group. Middleware applies its locale; policy-protected endpoints update it through a Form Request and action. Shared Inertia props expose current settings and ability; Vue pages use generated Wayfinder bindings.

**Tech Stack:** Laravel 13, PHP 8.5, Spatie Laravel Settings, Spatie Permission, Inertia v3, Vue 3, Wayfinder, Pest v4.

## Global Constraints

- Add only `spatie/laravel-settings`.
- Defaults: `Jalara Vue Starter Kit`, the approved README description, and `en`.
- Support only `en` and `id`.
- Register only `/settings/`, GET `/settings/general`, PATCH `/settings/general`; never add `/admin` or Style/Password settings.
- Every Settings route uses `auth`, `verified`, and policy authorization. Hide its sidebar link without `manage settings`.
- Retain the existing system-super-admin Gate bypass and Profile/Security ownership.
- Use generated Wayfinder functions, not URL strings. Export PHP translations to `lang/*.json`.
- Run `vendor/bin/pint --dirty --format agent` after PHP edits and focused Pest tests per task.

---

## File Structure

| Path | Responsibility |
| --- | --- |
| `app/Settings/GeneralSettings.php` | Typed Spatie `general` group. |
| `database/settings/*_create_general_settings.php` | Seeds global defaults. |
| `app/Enums/SiteLocale.php` | Allowed locale values and form options. |
| `app/Http/Middleware/SetApplicationLocale.php` | Sets locale on each web request. |
| `app/Policies/GeneralSettingsPolicy.php` | Maps view/update to `manage settings`. |
| `app/Actions/Settings/UpdateGeneralSettings.php` | Persists typed update data. |
| `app/Http/Requests/Settings/UpdateGeneralSettingsRequest.php` | Validates PATCH input. |
| `app/Http/Controllers/Settings/GeneralSettingsController.php` | Renders/updates Inertia Settings. |
| `routes/settings.php` | Owns the Settings route contract. |
| `resources/js/pages/settings/Index.vue` | One-card Settings landing page. |
| `resources/js/pages/settings/general/Edit.vue` | Localized General Settings form. |

### Task 1: Install and persist General Settings

**Files:**
- Modify: `composer.json`, `composer.lock`
- Create: `app/Settings/GeneralSettings.php`, `app/Enums/SiteLocale.php`
- Create: `database/settings/*_create_general_settings.php`
- Test: `tests/Feature/Settings/GeneralSettingsTest.php`

**Interfaces:**
- Produces: `GeneralSettings` properties `site_name`, `site_description`, `site_locale`; `SiteLocale::English` (`en`) and `SiteLocale::Indonesian` (`id`).

- [ ] **Step 1: Write failing default-settings coverage**

```php
<?php

use App\Enums\SiteLocale;
use App\Settings\GeneralSettings;

test('it provides the approved default general settings', function (): void {
    $settings = app(GeneralSettings::class);

    expect($settings->site_name)->toBe('Jalara Vue Starter Kit')
        ->and($settings->site_description)->toBe('Jalara provides a structured application foundation intended for projects that value modularity, flexibility, modern tooling, and clear organization.')
        ->and($settings->site_locale)->toBe(SiteLocale::English->value);
});
```

- [ ] **Step 2: Confirm the test fails**

Run: `php artisan test --compact tests/Feature/Settings/GeneralSettingsTest.php`

Expected: FAIL because the settings class and locale enum do not exist.

- [ ] **Step 3: Install and scaffold the approved package**

```bash
composer require spatie/laravel-settings
php artisan make:setting GeneralSettings --group=general --no-interaction
php artisan make:settings-migration CreateGeneralSettings --no-interaction
```

Keep the generated migration in `database/settings`; do not publish package configuration unless that directory is not discovered.

- [ ] **Step 4: Implement enum, settings class, and migration defaults**

```php
enum SiteLocale: string
{
    case English = 'en';
    case Indonesian = 'id';

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(static fn (self $locale): array => [
            'value' => $locale->value,
            'label' => $locale === self::English ? 'English' : 'Indonesian',
        ], self::cases());
    }
}
```

Make `GeneralSettings` extend `Spatie\LaravelSettings\Settings`, declare the three public string properties, and return `general` from `group()`. In the generated migration, add:

```php
$blueprint->add('site_name', 'Jalara Vue Starter Kit');
$blueprint->add('site_description', 'Jalara provides a structured application foundation intended for projects that value modularity, flexibility, modern tooling, and clear organization.');
$blueprint->add('site_locale', SiteLocale::English->value);
```

- [ ] **Step 5: Run migration and passing test**

```bash
php artisan migrate --no-interaction
php artisan test --compact tests/Feature/Settings/GeneralSettingsTest.php
```

Expected: migration and defaults test pass.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add composer.json composer.lock app/Settings/GeneralSettings.php app/Enums/SiteLocale.php database/settings tests/Feature/Settings/GeneralSettingsTest.php
git commit -m "feat: add general settings storage"
```

### Task 2: Apply persisted settings at runtime

**Files:**
- Create: `app/Http/Middleware/SetApplicationLocale.php`
- Modify: `bootstrap/app.php`, `app/Http/Middleware/HandleAppearance.php`, `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `resources/views/app.blade.php`, `resources/js/app.ts`
- Test: `tests/Feature/Settings/GeneralSettingsTest.php`, `tests/Feature/Localization/SharedLocaleTest.php`

**Interfaces:**
- Consumes: Task 1 `GeneralSettings`.
- Produces: shared `name` and `locale`; dynamic Blade/SPA title; current Laravel locale.

- [ ] **Step 1: Add failing locale/name behavior coverage**

```php
test('it applies the configured locale and shares the configured site name', function (): void {
    $settings = app(GeneralSettings::class);
    $settings->site_name = 'Configured Jalara';
    $settings->site_locale = SiteLocale::Indonesian->value;
    $settings->save();

    $this->get('/login')
        ->assertOk()
        ->assertSee('lang="id"', false)
        ->assertInertia(fn ($page) => $page
            ->where('name', 'Configured Jalara')
            ->where('locale', SiteLocale::Indonesian->value));

    expect(app()->getLocale())->toBe(SiteLocale::Indonesian->value);
});
```

Change `SharedLocaleTest` to persist Indonesian before requesting dashboard and assert the shared locale is `id`.

- [ ] **Step 2: Confirm runtime tests fail**

Run: `php artisan test --compact tests/Feature/Settings/GeneralSettingsTest.php tests/Feature/Localization/SharedLocaleTest.php`

Expected: FAIL because Laravel still reads config locale/name.

- [ ] **Step 3: Implement locale, view, and shared-prop propagation**

Create injected locale middleware:

```php
public function handle(Request $request, Closure $next): Response
{
    app()->setLocale($this->generalSettings->site_locale);

    return $next($request);
}
```

Append it before `HandleAppearance` and `HandleInertiaRequests` in the web stack. Inject `GeneralSettings` into `HandleAppearance` and share `siteName`. Change Inertia `name` to the stored setting and retain `locale => app()->getLocale()`.

Use `$siteName ?? config('app.name', 'Laravel')` in the Blade title. In `app.ts`, parse initial `#app[data-page]` JSON for `props.name` in the title callback, falling back to `VITE_APP_NAME || 'Laravel'`, as the sibling does.

- [ ] **Step 4: Run passing behavior tests**

Run: `php artisan test --compact tests/Feature/Settings/GeneralSettingsTest.php tests/Feature/Localization/SharedLocaleTest.php`

Expected: PASS; login HTML has `lang="id"`, Inertia shares configured values, and app locale is Indonesian.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Middleware bootstrap/app.php resources/views/app.blade.php resources/js/app.ts tests/Feature/Settings/GeneralSettingsTest.php tests/Feature/Localization/SharedLocaleTest.php
git commit -m "feat: apply general settings at runtime"
```

### Task 3: Add authorization, update flow, and routes

**Files:**
- Modify: `app/Enums/Permission.php`, `app/Authorization/AuthorizationCatalog.php`, `app/Providers/AppServiceProvider.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/types/global.d.ts`, `routes/web.php`
- Create: `app/Policies/GeneralSettingsPolicy.php`, `app/Actions/Settings/UpdateGeneralSettings.php`
- Create: `app/Http/Requests/Settings/UpdateGeneralSettingsRequest.php`, `app/Http/Controllers/Settings/GeneralSettingsController.php`, `routes/settings.php`
- Test: `tests/Feature/Settings/GeneralSettingsAccessTest.php`, `tests/Feature/Console/SyncAuthorizationTest.php`

**Interfaces:**
- Produces: `Permission::ManageSettings`, policy abilities `view`/ `update`, routes `settings.index`, `settings.general.edit`, `settings.general.update`.

- [ ] **Step 1: Write failing access, update, and validation tests**

```php
test('a user without manage settings cannot open or update general settings', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('settings.index'))->assertForbidden();
    $this->actingAs($user)->get(route('settings.general.edit'))->assertForbidden();
    $this->actingAs($user)->patch(route('settings.general.update'), [])->assertForbidden();
});

test('a user with manage settings can view and update general settings', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::ManageSettings->value);

    $this->actingAs($user)->get(route('settings.index'))
        ->assertOk()->assertInertia(fn ($page) => $page->component('settings/Index'));
    $this->actingAs($user)->get(route('settings.general.edit'))
        ->assertOk()->assertInertia(fn ($page) => $page->component('settings/general/Edit'));

    $this->actingAs($user)->patch(route('settings.general.update'), [
        'site_name' => 'Configured Jalara',
        'site_description' => '',
        'site_locale' => SiteLocale::Indonesian->value,
    ])->assertRedirect(route('settings.general.edit'));

    expect(app(GeneralSettings::class)->site_name)->toBe('Configured Jalara')
        ->and(app(GeneralSettings::class)->site_description)->toBe('')
        ->and(app(GeneralSettings::class)->site_locale)->toBe(SiteLocale::Indonesian->value);
});
```

Add a validation case for blank name, 1,001-character description, and `fr`, asserting all three errors. Extend authorization-sync coverage to assert `super-admin` owns `manage settings`.

- [ ] **Step 2: Confirm access tests fail**

Run: `php artisan test --compact tests/Feature/Settings/GeneralSettingsAccessTest.php tests/Feature/Console/SyncAuthorizationTest.php`

Expected: FAIL because permission, routes, and controller are absent.

- [ ] **Step 3: Implement authorization contract**

Add `case ManageSettings = 'manage settings';`, return it from `permissions()`, and map it to `Role::SuperAdmin` in `permissionsFor()`. Register `GeneralSettings::class` to `GeneralSettingsPolicy::class`; both policy methods return `$user->can(Permission::ManageSettings->value)`. In `HandleInertiaRequests`, add `auth.abilities.manage_settings` as `$user?->can(Permission::ManageSettings->value) ?? false` and type that nested boolean in `global.d.ts`. Do not modify Gate bypass behavior.

- [ ] **Step 4: Implement action, request, controller, and route contract**

The action API is:

```php
public function handle(GeneralSettings $generalSettings, array $data): void
{
    $generalSettings->site_name = $data['site_name'];
    $generalSettings->site_description = $data['site_description'];
    $generalSettings->site_locale = $data['site_locale'];
    $generalSettings->save();
}
```

The Form Request authorizes `update` against `GeneralSettings::class`, validates required `site_name|string|max:255`, nullable `site_description|string|max:1000`, and `Rule::enum(SiteLocale::class)`; its typed `payload()` always returns three strings.

The controller renders `settings/general/Edit` with `generalSettings` and `SiteLocale::options()`, calls the action, flashes a localized success toast, and redirects to `settings.general.edit`. Create `routes/settings.php` in an `auth` + `verified` group, define index GET/general GET/general PATCH, protect them with `->can()`, then require it from `routes/web.php`.

- [ ] **Step 5: Verify routes and passing tests**

```bash
php artisan test --compact tests/Feature/Settings/GeneralSettingsAccessTest.php tests/Feature/Console/SyncAuthorizationTest.php
php artisan route:list --path=settings
```

Expected: tests pass and route list shows only index GET, general GET, general PATCH.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/Permission.php app/Authorization/AuthorizationCatalog.php app/Providers/AppServiceProvider.php app/Http/Middleware/HandleInertiaRequests.php resources/js/types/global.d.ts app/Policies app/Actions/Settings app/Http/Requests/Settings app/Http/Controllers/Settings routes/settings.php routes/web.php tests/Feature/Settings/GeneralSettingsAccessTest.php tests/Feature/Console/SyncAuthorizationTest.php
git commit -m "feat: add general settings management"
```

### Task 4: Build localized pages and permission-aware navigation

**Files:**
- Create: `resources/js/pages/settings/Index.vue`, `resources/js/pages/settings/general/Edit.vue`, `resources/js/types/settings.ts`
- Modify: `resources/js/components/AppSidebar.vue`, `resources/js/components/AppLogo.vue`, `resources/js/types/index.ts`
- Modify: `lang/en/general.php`, `lang/id/general.php`, `lang/en.json`, `lang/id.json`
- Create/Modify generated: `resources/js/actions/App/Http/Controllers/Settings/GeneralSettingsController.ts`, `resources/js/routes/settings/*`

**Interfaces:**
- Consumes: generated Task 3 bindings, `auth.abilities.manage_settings`, shared `name`, and General Settings props.

- [ ] **Step 1: Add types and translations**

Create/export:

```ts
export type GeneralSettings = {
    site_name: string;
    site_description: string;
    site_locale: string;
};

export type SelectOption = {
    value: string;
    label: string;
};
```

Add a `settings` namespace to both language files for sidebar, landing/form headings/descriptions, labels, placeholders, open/save actions, and success toast. Every new template uses `useTrans()`.

- [ ] **Step 2: Generate locale and Wayfinder artifacts**

```bash
php artisan lang:export
php artisan wayfinder:generate --no-interaction
```

Expected: the JSON locales include Settings copy and generated imports resolve.

- [ ] **Step 3: Implement landing page and form**

`settings/Index.vue` uses existing layout/breadcrumb conventions and renders exactly one General Settings card linked by generated `settings.general.edit()`. `settings/general/Edit.vue` uses `useForm`, existing `Input`, `Textarea`, `Select`, `InputError`, and `Button`; it initializes/submits this shape through generated `update()` with `preserveScroll: true`:

```ts
type GeneralSettingsFormData = {
    site_name: string;
    site_description: string;
    site_locale: string;
};
```

Use `SelectOption[]` props for exactly two locales, `form.errors` for server validation, and `form.processing` for submit state.

- [ ] **Step 4: Update sidebar and logo**

In `AppSidebar.vue`, use `usePage()` and computed nav items: retain Dashboard and append Settings only if `page.props.auth.abilities.manage_settings` is true. Use a Lucide settings icon and generated `settings.index()`.

In `AppLogo.vue`, replace static `Laravel Starter Kit` with `page.props.name`, retaining icon/markup. Do not duplicate title logic.

- [ ] **Step 5: Run frontend checks**

```bash
pnpm run types:check
pnpm run lint:check
pnpm run build
```

Expected: all commands exit 0 and generated imports type-check.

- [ ] **Step 6: Commit frontend work**

```bash
git add resources/js lang/en/general.php lang/id/general.php lang/en.json lang/id.json
git commit -m "feat: add general settings interface"
```

### Task 5: Verify complete behavior

**Files:**
- Modify only if Tasks 1–4 reveal a defect.
- Test: `tests/Feature/Settings/GeneralSettingsTest.php`, `tests/Feature/Settings/GeneralSettingsAccessTest.php`, `tests/Feature/Localization/SharedLocaleTest.php`, `tests/Feature/Console/SyncAuthorizationTest.php`, `tests/Feature/Authorization/SuperAdminGateTest.php`

**Interfaces:**
- Consumes all prior routes, shared props, setting values, and generated assets.
- Produces evidence the feature works without an admin area or weakened authorization.

- [ ] **Step 1: Run focused regression tests**

```bash
php artisan test --compact tests/Feature/Settings/GeneralSettingsTest.php tests/Feature/Settings/GeneralSettingsAccessTest.php tests/Feature/Localization/SharedLocaleTest.php tests/Feature/Console/SyncAuthorizationTest.php tests/Feature/Authorization/SuperAdminGateTest.php
```

Expected: PASS for defaults, runtime locale/name, permission sync, authorized/forbidden requests, validation, and Gate bypass.

- [ ] **Step 2: Verify scope and generated artifacts**

```bash
php artisan route:list --path=settings
rg -n "settings/(general)?|settings\.general|manage_settings" app routes resources/js tests
git diff --check
```

Expected: only three approved endpoints, generated frontend route usage, no `/admin/settings`, and no whitespace errors.

- [ ] **Step 3: Run final static checks**

```bash
vendor/bin/pint --dirty --format agent
pnpm run types:check
pnpm run lint:check
```

Expected: all commands exit 0.

- [ ] **Step 4: Commit verification corrections only if needed**

```bash
git add app routes resources/js tests lang database composer.json composer.lock
git commit -m "test: verify general settings"
```
