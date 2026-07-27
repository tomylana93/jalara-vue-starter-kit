# General Settings Design

## Goal

Provide a global, permission-protected General Settings area where authorized
users can configure the application name, description, and locale. The feature
follows the `nova-starter-kit` settings architecture without introducing an
`/admin` area.

## Scope

- Provide a Settings landing page at `/settings/` and a General Settings form
  at `/settings/general`.
- Persist `site_name`, `site_description`, and `site_locale` as application
  settings using `spatie/laravel-settings`.
- Apply the persisted locale (`en` or `id`) to every web request.
- Share the persisted site name and active locale with Inertia, and show the
  site name in the initial document title, SPA page titles, and sidebar brand.
- Protect the feature with a `manage settings` permission. The permission is
  assigned to `super-admin`; the existing system-super-admin Gate bypass
  remains unchanged.

## Out of Scope

- An `/admin` route group or admin layout.
- Style, branding, password, or any other settings categories from
  `nova-starter-kit`.
- New locales beyond English and Indonesian.
- Changes to the existing Profile, Security, appearance, or authentication
  route ownership.

## Backend Design

`GeneralSettings` is a dedicated Spatie Settings group named `general`. Its
settings migration supplies these defaults:

| Setting | Default |
| --- | --- |
| `site_name` | `Jalara Vue Starter Kit` |
| `site_description` | `Jalara provides a structured application foundation intended for projects that value modularity, flexibility, modern tooling, and clear organization.` |
| `site_locale` | `en` |

A `SiteLocale` enum owns the only supported values, `en` and `id`, and produces
the select options consumed by the form. A dedicated update action accepts the
validated typed payload and persists all three values. The form request requires
`site_name` as a string of at most 255 characters, permits an empty
`site_description` with a 1,000-character maximum, and limits `site_locale` to
the enum.

The General Settings policy exposes `view` and `update`, both delegated to the
new `manage settings` permission. `Permission`, `AuthorizationCatalog`, and the
existing authorization-sync flow will include that permission and map it to
`Role::SuperAdmin`. `Gate::before` will continue to grant all abilities to a
system user with the `super-admin` role.

The app registers the General Settings policy, a locale middleware, and a
settings route file. The route contract is:

| Method | Path | Name | Ability |
| --- | --- | --- | --- |
| GET | `/settings/` | `settings.index` | `view` GeneralSettings |
| GET | `/settings/general` | `settings.general.edit` | `view` GeneralSettings |
| PATCH | `/settings/general` | `settings.general.update` | `update` GeneralSettings |

Each route uses the application's authenticated and verified-user middleware.
The locale middleware runs for each web request before rendering, setting
Laravel's locale from `GeneralSettings`. The Inertia shared props expose the
current `name`, `locale`, and a boolean `auth.abilities.manage_settings`.

## Frontend Design

The Settings landing page follows the sibling's card-based pattern, but contains
only one card: General Settings. It links to the General Settings form and is
not expanded with empty or unavailable Style/Password destinations.

The General Settings page uses the existing application layout, breadcrumbs,
page-wrapper/card/form controls, Wayfinder-generated controller action, and
toast behavior. It renders:

- a required application-name input;
- an optional application-description textarea; and
- an English/Indonesian locale select.

The Settings sidebar item is conditionally rendered only when
`auth.abilities.manage_settings` is true. Server-side policy checks remain the
authorization boundary, so users without that ability receive 403 responses on
direct requests.

All copy is added to both English and Indonesian language sources and included
in the generated frontend locale assets. The app shell receives the dynamic
site name rather than retaining its current static brand label. The initial
Blade document title and the Inertia SPA title callback use the same dynamic
name, including after an Inertia navigation.

## Testing Design

Feature tests exercise externally observable behavior:

- default General Settings values;
- locale application on a web request and shared site name in an Inertia
  response;
- permissions synchronized and assigned to `super-admin`;
- denied access for a user without `manage settings`;
- authorized rendering of both Settings pages; and
- successful persistence plus validation failures for the General Settings
  endpoint.

Implementation verification also runs the focused Pest suite, Laravel Pint on
modified PHP files, Wayfinder generation, and the relevant frontend type/lint
or build command. Tests should assert routes, responses, persisted values, and
shared props rather than controller internals.

## Constraints

- Add `spatie/laravel-settings` and follow its settings-migration convention,
  matching `nova-starter-kit`.
- Use Wayfinder route/action functions in Vue; do not hard-code URLs.
- Preserve the existing super-admin Gate bypass and existing non-settings route
  ownership.
- Do not add an admin area or unimplemented Settings categories.
