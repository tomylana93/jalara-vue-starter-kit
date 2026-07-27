# Laravel Lang + Frontend i18n Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port the `nova-starter-kit` localization architecture into this repo: PHP language files as source of truth, exported to untracked per-locale JSON, bundled by Vite, and consumed in Vue through a lightweight `useTrans()` composable. Share the active `locale` through Inertia props.

**Architecture:** `lang/{locale}/*.php` → `FrontendLocaleExporter` (`lang:export`) → `lang/{locale}.json` (git-ignored) → `import.meta.glob` in `useTrans.ts` → `trans()` keyed by the shared `locale` prop. A Vite plugin auto-exports on build and on dev file change.

**Tech Stack:** Laravel 13, PHP 8.5, Inertia 3, Vue 3, TypeScript, Vite, Tailwind CSS 4, Pest 4.

**Reference implementation (read-only source to copy from):** `/home/tomylana93/projects/nova-starter-kit`
- `app/Support/Localization/FrontendLocaleExporter.php`
- `app/Console/Commands/LangExportCommand.php`
- `resources/js/composables/useTrans.ts`
- `vite.config.ts` (`laravelLangExport` plugin, lines ~1-52)
- `lang/en/*.php`, `lang/id/*.php`, `.gitignore` (`/lang/*.json`), `package.json` script `lang:export`

## Global Constraints

- Do not add npm or composer dependencies.
- Exported `lang/*.json` files are build artifacts and MUST be git-ignored, never committed.
- Do NOT port `SetApplicationLocale` middleware or any `GeneralSettings`/spatie-settings coupling — active-locale source is a deferred decision. App locale stays config-driven (`APP_LOCALE=en`).
- Support exactly two locales for now: `en` (default/fallback) and `id`.
- Match this repo's existing conventions (PHP attribute-based command signature, `HandleInertiaRequests` shape, `resources/js/types` structure), not nova's verbatim paths where they differ.
- Write/adjust Pest tests before implementation for each backend behavior; run only affected tests per task; run `vendor/bin/pint --dirty --format agent` after PHP changes.
- Preserve unrelated uncommitted work in the tree (avatar/media-library changes).

## File map

| File | Responsibility |
| --- | --- |
| `lang/en/general.php`, `lang/en/auth.php` | Seed English source strings (starter namespaces). |
| `lang/id/general.php`, `lang/id/auth.php` | Indonesian counterparts (same keys). |
| `app/Support/Localization/FrontendLocaleExporter.php` | Read PHP lang dirs, write per-locale JSON; hard error on bad input. |
| `app/Console/Commands/LangExportCommand.php` | `lang:export` command wrapping the exporter with `--locale`/`--path`. |
| `.gitignore` | Ignore `/lang/*.json`. |
| `vite.config.ts` | Add `laravelLangExport()` plugin (buildStart export + dev watch/reload). |
| `package.json` | Add `"lang:export": "php artisan lang:export"` script. |
| `app/Http/Middleware/HandleInertiaRequests.php` | Share `'locale' => app()->getLocale()`. |
| `resources/js/types/global.d.ts` | Add `locale: string` to `sharedPageProps`. |
| `resources/js/composables/useTrans.ts` | `trans()` / `useTrans()` reading globbed JSON + shared locale. |
| `tests/Feature/Localization/FrontendLocaleExporterTest.php` | Cover exporter + `lang:export` command. |
| `tests/Feature/.../*` (shared props) | Assert `locale` present in Inertia props. |

---

## Task 1 — Seed PHP language files

- [ ] Create `lang/en/general.php` and `lang/en/auth.php` with a small set of starter keys (e.g. `general.save`, `general.cancel`, `general.dashboard`; reuse Laravel's default `auth.failed`/`auth.throttle` wording).
- [ ] Create `lang/id/general.php` and `lang/id/auth.php` with identical key sets, Indonesian values.
- [ ] Verify each file returns a PHP array. No test yet (data files).

## Task 2 — Exporter service + command (TDD)

- [ ] Write `tests/Feature/Localization/FrontendLocaleExporterTest.php`:
  - Exports `['en','id']` to a temp dir; assert both JSON files exist and decode to `{namespace: {...}}` with expected nested keys.
  - Asserts a `RuntimeException` (or command failure) on a missing locale dir and on a lang file that does not return an array.
  - Runs `artisan('lang:export', ['--path' => tempDir])`; assert exit code 0 and "Exported [en]" style output.
- [ ] Port `App\Support\Localization\FrontendLocaleExporter` from nova (Filesystem-based, `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR`, trailing `PHP_EOL`).
- [ ] Port `App\Console\Commands\LangExportCommand` (attribute `#[Signature]`/`#[Description]`, `--locale=*`, `--path=`).
- [ ] Run the test file; run Pint.

## Task 3 — Git ignore + npm script + manual export

- [ ] Add `/lang/*.json` to `.gitignore`.
- [ ] Add `"lang:export": "php artisan lang:export"` to `package.json` scripts.
- [ ] Run `php artisan lang:export`; confirm `lang/en.json` + `lang/id.json` generated and that `git status` shows them ignored.

## Task 4 — Vite auto-export plugin

- [ ] Add `laravelLangExport()` plugin to `vite.config.ts` (port `execFileSync('php', ['artisan','lang:export'])`, `buildStart` throwing on failure, `configureServer` watching `lang/**/*.php` → re-export → `full-reload`). Place it first in the `plugins` array.
- [ ] Verify `pnpm run build` regenerates JSON (build succeeds). Note: dev-watch behavior is verified manually by the user, not in CI.

## Task 5 — Share locale (TDD)

- [ ] Add/extend a Pest feature test asserting the Inertia shared props include `locale` equal to `app()->getLocale()` (use an authenticated page render + Inertia assertion helper).
- [ ] Add `'locale' => app()->getLocale()` to `HandleInertiaRequests::share()`.
- [ ] Add `locale: string` to the `sharedPageProps` interface in `resources/js/types/global.d.ts`.
- [ ] Run the affected test; run Pint.

## Task 6 — Frontend composable

- [ ] Port `resources/js/composables/useTrans.ts` from nova, adapted to this repo's typing: read `locale` from `usePage()` shared props (the `sharedPageProps` declaration now includes `locale`), glob `../../../lang/*.json` eagerly, implement `getMessage` (dot-path walk + `en` fallback), `replacePlaceholders` (`:key`/`:Key`/`:KEY`), `trans`, and `useTrans`.
- [ ] If the repo's `import.meta.glob` type (in `global.d.ts`) is the lazy signature, use a local eager-glob type/cast so `{ eager: true }` type-checks.
- [ ] Run `pnpm run types:check` (or `vue-tsc --noEmit`) to confirm no type errors.

## Task 7 — Verify end to end

- [ ] Run the full affected Pest suite (`php artisan test --compact --filter=Localization` plus the shared-props test).
- [ ] Run `pnpm run lint:check` and `pnpm run types:check`.
- [ ] Run `vendor/bin/pint --dirty --format agent`.
- [ ] Manually (user) confirm a component using `useTrans().trans('general.save')` renders correctly and that editing a `lang/**/*.php` file under `vite dev` hot-reloads.

## Deferred (separate follow-up, not this plan)

- Active-locale source + persistence (cookie recommended, consistent with existing `appearance`/`sidebar_state` cookies) and a language-switcher UI + `SetApplicationLocale` middleware.
- Additional namespaces (dashboard, settings, nav, etc.) as pages are localized.
