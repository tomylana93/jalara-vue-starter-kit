# Root Route Authentication Redirect Design

## Goal

Refactor the `/` route so it acts as the application entry point:

- Guests are redirected to the named `login` route.
- Authenticated users are redirected to Laravel's intended destination.
- When no intended destination exists, authenticated users are redirected to the named `dashboard` route.

The existing `home` route name remains available for logout and other callers.

## Current State

`routes/web.php` currently renders the `Welcome` Inertia page at `/`. The dashboard is an authenticated and verified route at `/dashboard`. Fortify already uses `/dashboard` as its configured authentication home path, and the application has feature tests for dashboard protection, login, and logout.

## Selected Approach

Replace the `Route::inertia('/', 'Welcome')` definition with a small route callback that branches on the current web session:

```php
Route::get('/', function (Request $request): RedirectResponse {
    if ($request->user() === null) {
        return redirect()->route('login');
    }

    return redirect()->intended(route('dashboard'));
})->name('home');
```

The callback is intentionally kept in the route file because the behavior is limited to one entry-point redirect and does not yet justify a controller or reusable middleware abstraction.

## Request Flow

1. A guest requests `/`.
2. The route detects there is no authenticated web user.
3. The route redirects to the named `login` route.
4. An authenticated user requests `/`.
5. The route calls `redirect()->intended(route('dashboard'))`.
6. Laravel uses the session's intended URL when present; otherwise it uses `/dashboard`.

Protected-route behavior remains unchanged. For example, a guest requesting `/dashboard` is redirected to login by `auth` middleware and Laravel may record `/dashboard` as the intended URL. After authentication, Fortify can return the user to that destination.

## Scope

### In scope

- Replace the root `Welcome` render route with the authenticated/guest redirect behavior.
- Delete the now-unreachable `resources/js/pages/Welcome.vue` page.
- Remove the `Welcome`-specific layout exception from `resources/js/app.ts`.
- Preserve the `home` route name.
- Add focused Pest feature coverage for guest, authenticated fallback, and authenticated intended-destination behavior.
- Keep the existing `/dashboard` route and Fortify home configuration unchanged.

### Out of scope

- Redesigning the replacement login or dashboard pages.
- Changing shared application layouts or other page components.
- Changing Fortify login, logout, or two-factor response behavior.
- Adding a controller, middleware, service, or dependency.
- Changing the destination used by routes other than `/`.

## Acceptance Criteria

- `GET /` as a guest returns a redirect to `route('login')`.
- `GET /` as an authenticated user with no intended URL returns a redirect to `route('dashboard')`.
- `GET /` as an authenticated user with an intended URL returns a redirect to that URL.
- The redirect uses named routes rather than hardcoded application URLs where a named route exists.
- No remaining application source references the removed `Welcome` page or its layout exception.
- Existing dashboard, login, and logout tests remain passing.

## Testing Strategy

Extend the existing root/dashboard feature coverage with Pest tests using `User::factory()` and `actingAs()`:

- Guest root redirect: assert `GET route('home')` redirects to `route('login')`.
- Authenticated fallback: assert `GET route('home')` redirects to `route('dashboard')` when the session has no intended URL.
- Intended destination: seed the session's intended URL using Laravel's existing session/intended redirect mechanism, then assert the root response redirects to that URL.

Run a repository search for `Welcome` after the cleanup and confirm it returns no application source references. Run the relevant frontend checks for the changed TypeScript/Vue surface in addition to the focused backend test.

Run the focused feature test first with `php artisan test --compact` and the relevant test filename, then run Pint if PHP files were changed.

## Risks and Mitigations

- **Redirect loop:** The guest branch targets Fortify's public `login` route, while the authenticated branch targets the protected dashboard, so neither branch points back to `/`.
- **Logout behavior change:** Logout already redirects to the named `home` route. After logout, the guest branch intentionally sends the user to login; this is part of the new entry-point behavior.
- **Unexpected intended URL:** Laravel's intended redirect is session-backed and is only used for authenticated users. The fallback remains the dashboard route.
