# Root Route Authentication Redirect Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `/` redirect guests to login and authenticated users to their intended destination, falling back to `/dashboard`, while removing the unreachable Welcome UI.

**Architecture:** Replace the root Inertia render route with a typed route callback that checks the current web user. Guest requests go to the named login route; authenticated requests use `redirect()->intended(route('dashboard'))`. Delete the obsolete Welcome page and remove its dedicated layout exception from the Inertia app bootstrap.

**Tech Stack:** Laravel 13, Laravel Fortify, Inertia.js v3, Vue 3, Pest 4, TypeScript, ESLint, Prettier, vue-tsc.

## Global Constraints

- Keep the existing `home` route name.
- Use named routes for login and dashboard destinations.
- Preserve the existing authenticated and verified dashboard route.
- Do not add dependencies, controllers, middleware, services, or new base directories.
- Delete `resources/js/pages/Welcome.vue` because the root route no longer renders it.
- Remove only the `Welcome`-specific layout exception; leave auth, settings, dashboard, and shared layouts unchanged.
- Every change must be covered by focused automated verification.
- Run Pint when PHP files are modified.

---

## File Map

- Modify: `routes/web.php` — replace the `Welcome` Inertia route with the guest/authenticated redirect callback.
- Modify: `tests/Feature/DashboardTest.php` — add root-route redirect coverage for guest, authenticated fallback, and intended destination behavior.
- Modify: `resources/js/app.ts` — remove the obsolete `name === 'Welcome'` layout branch.
- Delete: `resources/js/pages/Welcome.vue` — remove the unreachable Laravel starter page.
- Do not modify: `config/fortify.php` — its existing `home` value already remains `/dashboard`.

---

### Task 1: Add Root Redirect Feature Tests

**Files:**
- Modify: `tests/Feature/DashboardTest.php`

**Interfaces:**
- Consumes: named routes `home`, `login`, `dashboard`, and `settings.profile`; `App\\Models\\User` factory; Laravel session key `url.intended`.
- Produces: three regression tests that define the required root-route behavior before implementation.

- [ ] **Step 1: Add the guest root redirect test**

Add this test after the existing guest dashboard test:

```php
test('guests are redirected from the root route to the login page', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});
```

- [ ] **Step 2: Add the authenticated fallback redirect test**

Add this test after the guest root test:

```php
test('authenticated users are redirected from the root route to the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertRedirect(route('dashboard'));
});
```

- [ ] **Step 3: Add the intended destination redirect test**

Add this test after the authenticated fallback test. Store a relative named-route URL in Laravel's intended session key:

```php
test('authenticated users are redirected from the root route to the intended destination', function () {
    $user = User::factory()->create();
    $intendedUrl = route('settings.profile', absolute: false);

    $response = $this->actingAs($user)
        ->withSession(['url.intended' => $intendedUrl])
        ->get(route('home'));

    $response->assertRedirect($intendedUrl);
});
```

- [ ] **Step 4: Run the new tests before implementation**

Run:

```bash
php artisan test --compact tests/Feature/DashboardTest.php
```

Expected: the existing dashboard tests pass, the new guest test fails because `/` still renders the Welcome page, and the authenticated redirect tests fail because `/` still returns the Inertia page rather than a redirect.

- [ ] **Step 5: Commit the failing tests**

Run:

```bash
git add tests/Feature/DashboardTest.php
git commit -m "test: define root authentication redirects"
```

---

### Task 2: Implement the Root Redirect

**Files:**
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: `Illuminate\\Http\\RedirectResponse`, `Illuminate\\Http\\Request`, named routes `login` and `dashboard`.
- Produces: `GET /` behavior that redirects guests to login and authenticated users to their intended URL or dashboard.

- [ ] **Step 1: Replace the root Inertia route**

Update the imports and root route in `routes/web.php` to:

```php
<?php

use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Route;

Route::get('/', function (Request $request): RedirectResponse {
    if ($request->user() === null) {
        return redirect()->route('login');
    }

    return redirect()->intended(route('dashboard'));
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
```

This preserves the `home` name used by logout while making the root response a redirect for both authentication states.

- [ ] **Step 2: Run the focused route tests**

Run:

```bash
php artisan test --compact tests/Feature/DashboardTest.php
```

Expected: all tests in `DashboardTest.php` pass, including the three new root redirect cases and the existing dashboard access cases.

- [ ] **Step 3: Format the modified PHP files**

Run:

```bash
vendor/bin/pint --dirty --format agent
```

Expected: Pint completes successfully and leaves the route/test files formatted according to project conventions.

- [ ] **Step 4: Re-run the focused tests after formatting**

Run:

```bash
php artisan test --compact tests/Feature/DashboardTest.php
```

Expected: all tests pass.

- [ ] **Step 5: Commit the route implementation**

Run:

```bash
git add routes/web.php tests/Feature/DashboardTest.php
git commit -m "refactor: redirect root route by authentication state"
```

---

### Task 3: Remove the Unreachable Welcome UI

**Files:**
- Modify: `resources/js/app.ts`
- Delete: `resources/js/pages/Welcome.vue`

**Interfaces:**
- Consumes: the existing Inertia page-name layout resolver.
- Produces: the same layout behavior for all remaining pages without a dead `Welcome` branch.

- [ ] **Step 1: Remove the Welcome layout branch**

In `resources/js/app.ts`, change the layout resolver from:

```ts
layout: (name) => {
    switch (true) {
        case name === 'Welcome':
            return null;
        case name.startsWith('auth/'):
            return AuthLayout;
        case name.startsWith('settings/'):
            return [AppLayout, SettingsLayout];
        default:
            return AppLayout;
    }
},
```

to:

```ts
layout: (name) => {
    switch (true) {
        case name.startsWith('auth/'):
            return AuthLayout;
        case name.startsWith('settings/'):
            return [AppLayout, SettingsLayout];
        default:
            return AppLayout;
    }
},
```

- [ ] **Step 2: Delete the unused page**

Delete:

```
resources/js/pages/Welcome.vue
```

No replacement page is needed because `GET /` now redirects before Inertia page resolution.

- [ ] **Step 3: Verify no application references remain**

Run:

```bash
rg -n "Welcome|welcome" app bootstrap config resources routes tests --glob '!storage/**'
```

Expected: no output. References in the committed spec are allowed; application source must contain none.

- [ ] **Step 4: Run frontend static checks**

Run:

```bash
pnpm run lint:check
pnpm run types:check
pnpm run format:check
```

Expected: ESLint, Vue TypeScript checking, and Prettier all pass.

- [ ] **Step 5: Commit the UI cleanup**

Run:

```bash
git add resources/js/app.ts
git add -u resources/js/pages/Welcome.vue
git commit -m "chore: remove obsolete welcome page"
```

---

### Task 4: Run Final Verification

**Files:**
- Verify: `routes/web.php`
- Verify: `tests/Feature/DashboardTest.php`
- Verify: `resources/js/app.ts`
- Verify deletion: `resources/js/pages/Welcome.vue`

**Interfaces:**
- Consumes: all changes from Tasks 1–3.
- Produces: verified route behavior and a clean frontend/backend change set.

- [ ] **Step 1: Run the focused backend tests**

Run:

```bash
php artisan test --compact tests/Feature/DashboardTest.php
```

Expected: all dashboard and root redirect tests pass.

- [ ] **Step 2: Run the authentication regression tests**

Run:

```bash
php artisan test --compact tests/Feature/Auth/AuthenticationTest.php
```

Expected: login, two-factor, invalid-password, logout, and throttling tests pass. Logout should still redirect to the named `home` route, which now sends the guest to login.

- [ ] **Step 3: Run frontend verification**

Run:

```bash
pnpm run lint:check
pnpm run types:check
pnpm run format:check
```

Expected: all commands exit successfully.

- [ ] **Step 4: Inspect the final diff**

Run:

```bash
git diff HEAD~3..HEAD --check
git status --short
```

Expected: no whitespace errors and no unintended uncommitted changes. If commits were squashed during implementation, inspect the equivalent final diff instead of relying on the exact `HEAD~3` range.

- [ ] **Step 5: Confirm the implementation against the spec**

Confirm all of the following:

- Guest `GET /` redirects to `login`.
- Authenticated `GET /` redirects to an existing intended URL.
- Authenticated `GET /` falls back to `dashboard`.
- `home` remains the route name.
- `Welcome.vue` is deleted.
- No application source references `Welcome`.
- Dashboard protection and existing authentication behavior remain intact.

