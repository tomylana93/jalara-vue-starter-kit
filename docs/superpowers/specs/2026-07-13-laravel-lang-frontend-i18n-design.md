# Laravel language files with frontend i18n (port from nova-starter-kit)

## Goal

Introduce Laravel localization to the app and consume it in the Inertia/Vue frontend. PHP language files are the single source of truth; they are exported to per-locale JSON that Vite bundles and a lightweight composable reads. The architecture is ported verbatim from the sibling `nova-starter-kit` project, adapted to this repo.

## Scope

- Author translations as PHP array files under `lang/{locale}/{namespace}.php`.
- Export PHP language files to per-locale JSON via an artisan command (`lang:export`) backed by a reusable exporter service.
- Keep exported JSON out of git (`/lang/*.json` is a build artifact).
- Auto-run the export on Vite `buildStart` and on `lang/**/*.php` change during `vite dev` (full reload).
- Share the active `locale` string through the default Inertia shared props.
- Consume translations in Vue via a custom `useTrans()` / `trans()` composable that bundles all locale JSON with `import.meta.glob` and selects by the shared `locale`.
- Support two locales: `en` (default/fallback) and `id`.

Out of scope (deferred, decided later):

- **Active-locale source & language switcher.** How the runtime locale is chosen and persisted (cookie / session / config / per-user) is intentionally not decided yet. For this port the app locale stays whatever Laravel resolves from config (`APP_LOCALE=en`); no `SetApplicationLocale` middleware and no switcher UI are added. `nova-starter-kit`'s middleware depends on `GeneralSettings::site_locale` (spatie settings), which this repo does not have, so it is deliberately omitted.
- Pluralization helpers beyond simple placeholder replacement.
- Translating backend validation/framework messages (only the app's own PHP lang files are exported; no vendor `lang` publish required for this port).

## Source of truth and formats

- **Source:** `lang/en/*.php` and `lang/id/*.php`, each returning a nested PHP array. One file per namespace (e.g. `auth.php`, `general.php`).
- **Generated:** `lang/en.json` and `lang/id.json`, each an object keyed by namespace → nested messages. Written with `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE` plus a trailing newline. These are **untracked**.
- Translation keys in the frontend are dot-paths across namespace + nested keys, e.g. `trans('auth.failed')`, `trans('general.save')`.

## Export design (`FrontendLocaleExporter` + `lang:export`)

- A service `App\Support\Localization\FrontendLocaleExporter` reads each locale directory under `lang_path()`, loads every `*.php` file as a namespace, and writes `{outputDir}/{locale}.json`.
- Output directory defaults to `lang_path()`; overridable via `--path`.
- Locales default to every subdirectory of `lang/`; scopable via `--locale=*`.
- A missing locale directory or a non-array language file is a hard error (the command reports it and returns failure).
- The `lang:export` command wraps the service, prints each written file, and returns success/failure exit codes.

## Vite integration

- A `laravelLangExport()` Vite plugin runs `php artisan lang:export` on `buildStart` (throwing if it fails) and, in dev, watches `lang/**/*.php`, re-exports on change, and triggers a full reload.
- Exposed as a `pnpm run lang:export` script (`php artisan lang:export`) for manual runs.

## Backend sharing

- `HandleInertiaRequests::share()` adds `'locale' => app()->getLocale()` to the existing shared props (`name`, `auth`, `sidebarOpen`).

## Frontend consumption

- `resources/js/composables/useTrans.ts`:
  - Eagerly globs `../../../lang/*.json` and builds a `Record<locale, messages>` map.
  - `getMessage(locale, key)` walks the dot-path; falls back to the `en` map, then returns `null`.
  - `replacePlaceholders(message, replacements)` supports `:key`, `:Key` (ucfirst), and `:KEY` (upper) Laravel-style placeholders.
  - `trans(key, replacements?, locale?)` returns the resolved string, or the raw key when missing.
  - `useTrans()` reads `locale` from `usePage()` shared props and returns `{ locale, trans }` bound to the active locale.
- The Inertia shared-props type (`sharedPageProps` in `resources/js/types/global.d.ts`) gains `locale: string`.

## Testing

- Feature/unit test for `FrontendLocaleExporter`: exports known locales to a temp dir, asserts JSON structure, namespace nesting, and error on missing dir / non-array file.
- Feature test for `lang:export` command: runs it against a temp `--path`, asserts written files and success output.
- Assert `locale` appears in the Inertia shared props (extend an existing shared-props test if present).

## Acceptance

- `php artisan lang:export` writes `lang/en.json` and `lang/id.json`; both are git-ignored.
- `vite build` regenerates the JSON automatically; editing a `lang/**/*.php` file under `vite dev` reloads the page with updated strings.
- A Vue component calling `useTrans().trans('general.save')` renders the `en` string, and `trans('auth.failed', {}, 'id')` renders the `id` string.
- Missing keys render the key itself; no runtime error.
