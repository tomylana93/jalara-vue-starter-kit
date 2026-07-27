# Centralized Backend and Frontend Localization Design

## Status

Approved through a `/grill-me` session on 2026-07-16. This design supersedes
the deferred decisions in
`docs/superpowers/specs/2026-07-13-laravel-lang-frontend-i18n-design.md`
without replacing its already implemented export pipeline.

## Problem

The application already authors Laravel translations in
`lang/{locale}/*.php`, exports them to ignored `lang/{locale}.json` assets, and
reads those assets through `useTrans()` in Vue. Adoption is incomplete:

- user-facing copy remains hardcoded across Vue pages, layouts, and shared
  components;
- some backend presentation messages use English sentences as translation
  keys, while other messages are not localized;
- locale files are not checked for matching keys, string leaves, or matching
  placeholders;
- frontend callers accept arbitrary string keys, so typos survive type-check;
- the frontend helper has no pluralization or safe rich-text interface;
- CI does not prevent new hardcoded user-facing copy.

The result is a localization mechanism without a complete, enforceable
localization contract.

## Goals

- Make `lang/{locale}/*.php` the only human-edited source of user-facing copy
  for both Laravel and Vue.
- Localize all application-owned user-facing backend and frontend copy in
  English (`en`) and Indonesian (`id`).
- Preserve the global locale selected by `GeneralSettings.site_locale`.
- Give frontend callers a typed, small localization interface.
- Validate locale structure, placeholders, and generated artifacts before
  deployment.
- Support Laravel-style replacements, capitalization, and pluralization on
  both tiers.
- Prevent new hardcoded user-facing copy outside the explicit exclusions.
- Keep every migration slice deployable and independently reviewable.

## Non-goals

- Per-user, session, cookie, browser-preference, or route-based locale
  selection.
- A language switcher beyond the existing global general setting.
- Localization of internal exceptions, logs, developer diagnostics, or
  Artisan command descriptions and output.
- Editing or auditing `resources/js/components/ui/**`.
- Adding `vue-i18n` or another localization runtime dependency.
- Lazy-loading locale bundles while only `en` and `id` are supported.
- Machine translation or automatic judgment of translation quality.

Generated Wayfinder files and other machine-generated frontend code contain
no application-owned presentation copy and are not migration targets.

## Decisions

### Locale ownership

`GeneralSettings.site_locale` remains the sole runtime locale selector.
`SetApplicationLocale` applies it to Laravel before application code produces
localized output. `HandleInertiaRequests` shares the resolved locale string
with Vue. Changing the global locale takes effect on the next request; no
client-only locale state is introduced.

The localization interfaces accept an explicit locale internally or as an
optional override so a future locale-selection mechanism can replace the
source without changing every caller. That future mechanism is not part of
this refactor.

### Source of truth and generated artifacts

Human-authored translations live only in:

```text
lang/
  en/*.php
  id/*.php
```

Each file is a feature namespace containing nested semantic keys. The export
pipeline produces:

- `lang/en.json` and `lang/id.json`, which remain git-ignored build assets;
- a deterministic tracked TypeScript file containing the `TranslationKey`
  union used by frontend callers.

Generated files must never be edited manually. Generation runs during Vite
build and development as it does today. CI regenerates the tracked key
contract and fails when the working tree changes, proving that it was current
at commit time. The designated integrator is the sole owner of generated
artifacts during implementation.

### Namespace and key convention

Translation files are organized by application feature or presentation
domain, including at least:

- `auth.php`
- `profile.php`
- `security.php`
- `settings.php`
- `navigation.php`
- `general.php`
- Laravel framework-facing files such as `validation.php` and `passwords.php`
  when their messages reach users

Keys describe meaning and context rather than copying the English text:

```text
auth.login.action.submit
profile.form.label.name
security.passkeys.empty.title
settings.general.toast.updated
```

Sentence keys such as `__('Profile updated.')` are prohibited. A key may be
shared across backend and frontend only when both callers express the same
meaning. Reuse must not erase feature context merely to reduce the number of
strings.

Every new or changed key must include both `en` and `id` translations in the
same change. Language choices use recognizable endonyms: `English` and
`Bahasa Indonesia`. These labels are resolved from translation keys rather
than embedded in `SiteLocale::options()`.

### Tier ownership

The tier that owns a message translates it:

- Laravel translates validation, authentication, authorization, flash/toast,
  notification, mail, and other server-originated presentation messages.
- Vue translates page titles, headings, descriptions, actions, labels,
  placeholders, empty states, navigation, accessibility text, and other
  client-owned copy.
- Laravel sends resolved server messages to Vue. It does not send translation
  keys for Vue to translate again.

Internal exceptions, logs, and CLI output remain stable English diagnostics.
They must not be routed through the presentation translation catalog.

### Frontend module and interface

The frontend localization module is a deep module: loading, fallback,
dot-path lookup, replacements, plural selection, and locale binding remain
behind a small caller interface.

Its public interface is conceptually:

```ts
export type TranslationKey = /* generated union */;
export type Replacements = Record<string, number | string>;

export function trans(
    key: TranslationKey,
    replacements?: Replacements,
    locale?: string,
): string;

export function transChoice(
    key: TranslationKey,
    count: number,
    replacements?: Replacements,
    locale?: string,
): string;

export function useTrans(): {
    locale: ComputedRef<string>;
    trans: typeof trans;
    transChoice: typeof transChoice;
};
```

The implementation eagerly loads both generated JSON locale assets. Missing
locale lookup falls back to `en`. A missing key falls back to the raw key in
production as a last-resort safety behavior, although validation and typing
are expected to make that path unreachable in committed code.

Replacement behavior remains compatible with Laravel's `:name`, `:Name`, and
`:NAME` capitalization forms. `transChoice` selects Laravel-compatible
plural branches, injects `count` into the replacement map, and is covered by
cross-tier parity examples for `en` and `id`. Callers must not implement their
own singular/plural conditionals.

Rich text is rendered by a small localization component outside
`resources/js/components/ui/**`. It resolves a typed translation key and
allows named Vue slots at declared placeholders, so links or emphasis can be
inserted without `v-html`. Translation strings are never treated as trusted
HTML.

### Backend interface

Backend application code continues to use Laravel's `__()` and
`trans_choice()` helpers with semantic keys. Framework authentication,
password reset, email verification, throttling, and validation messages that
are visible to users are published or supplied in both supported locales.

`SiteLocale` remains the allow-list for supported global locale values. Its
form options resolve translated endonym labels at call time rather than
storing presentation copy in the enum.

### Validation contract

English is the canonical catalog shape. Before writing any generated asset,
the exporter validates every selected locale against it:

1. Every canonical key exists in every supported locale.
2. No supported locale contains an unknown extra key.
3. Every leaf value is a string; arrays may only be namespace/branch nodes.
4. Each locale uses the same placeholder names for a given key.
5. Plural messages have a supported Laravel-compatible form.
6. Generated JSON and TypeScript output is deterministic.

Validation is all-or-nothing: the exporter validates the complete catalog
before replacing output files. On failure it reports the locale, key, and
contract violation, exits unsuccessfully, and leaves the previous generated
assets intact. Development reload occurs only after a successful export.

Runtime fallback to English protects production rendering; it is not a
substitute for passing catalog validation.

## Scope of user-facing copy

Included frontend locations are all application-owned Vue/TypeScript files
under `resources/js` except `resources/js/components/ui/**`. This includes
pages, layouts, ordinary components, composables that return presentation
copy, accessible-only text, dialog copy, labels, placeholders, titles,
descriptions, empty states, buttons, and toast content.

Included backend locations are presentation-producing application code such
as controllers, form requests, Fortify actions and responses, notifications,
mail, and user-visible validation/authentication messages. Domain and
infrastructure exceptions are excluded unless their exact text is deliberately
shown to an end user; in that case the presentation adapter translates a
semantic error rather than localizing the internal exception message.

## Hardcoded-copy enforcement

Frontend lint or an equivalent AST-aware audit examines Vue text nodes and
user-facing attributes/props such as `title`, `description`, `label`,
`placeholder`, and `aria-label`. It excludes
`resources/js/components/ui/**`. Necessary literals such as a product name or
example email require a narrow, documented inline suppression; broad file or
directory allow-lists are not acceptable.

A targeted backend architecture test audits presentation-producing locations
for newly introduced user-facing literals. It excludes internal exceptions,
logs, and Artisan output. The migration establishes an explicit baseline and
then removes it feature by feature; new violations fail CI immediately.

The audit complements catalog validation and TypeScript typing. It is not
expected to infer translation quality.

## Migration strategy

Migration uses deployable tracer bullets:

1. Strengthen the exporter and frontend localization interface, add generated
   key typing, pluralization, safe rich text, and contract tests.
2. Migrate backend user-facing copy and framework messages to semantic keys.
3. Migrate frontend shell and navigation copy.
4. Migrate authentication copy.
5. Migrate profile copy.
6. Migrate security, two-factor authentication, and passkey copy.
7. Migrate settings and remaining application-owned frontend copy.
8. Enable the final hardcoded-copy gates and remove obsolete sentence keys.

Every slice adds `en` and `id` together, keeps both tiers buildable, and
removes old keys only after reference searches prove they are unused. No step
edits `resources/js/components/ui/**`.

Rollback is code-only: revert the affected migration slice, regenerate JSON
and the TypeScript key contract, and redeploy. There is no database migration,
data backfill, or persistent user preference to reverse.

## Testing and verification

The implementation must provide:

- unit tests for frontend lookup, English fallback, replacements,
  capitalization, plural selection, and rich-text slot interpolation;
- exporter feature tests for matching keys, rejection of extra/missing keys,
  string-only leaves, placeholder parity, plural syntax, deterministic JSON,
  deterministic TypeScript keys, and atomic failure behavior;
- backend feature tests proving the global locale controls Inertia props,
  validation, authentication, flash/toast, and relevant notification/mail
  messages;
- TypeScript checks proving invalid frontend keys fail compilation;
- localization audits proving hardcoded user-facing copy is rejected while
  the `components/ui` exclusion and narrow suppressions work;
- browser smoke coverage in `en` and `id` for login, profile, security, and
  general settings, asserting that no raw translation key or empty localized
  value is rendered;
- the repository finishing gate defined by `AGENTS.md`.

Each implementation task follows red-green-refactor and receives independent
review because the overall refactor is classified Deep.

## Acceptance criteria

- All application-owned user-facing backend and frontend copy is resolved
  through semantic translation keys, excluding only the documented scope.
- `resources/js/components/ui/**` is unchanged by the migration.
- `en` and `id` catalogs pass key, leaf-type, placeholder, and pluralization
  validation.
- The application still selects locale globally through
  `GeneralSettings.site_locale`.
- Backend-originated messages are translated on the backend; frontend-owned
  copy is translated in Vue.
- Frontend translation calls accept generated `TranslationKey` values and
  support replacements and pluralization.
- Rich localized copy containing links or emphasis renders without `v-html`.
- Generated JSON remains ignored; the deterministic generated TypeScript key
  contract is tracked and verified by CI.
- Hardcoded-copy audits fail on newly introduced presentation literals outside
  the documented exclusions.
- Targeted tests, two-locale browser smoke tests, and the full finishing gate
  pass.
- No database, dependency, authentication behavior, authorization behavior,
  or public route contract changes are introduced.
- After implementation, independent review, and full verification are
  complete, the integrator creates or updates a Serena memory describing the
  durable localization architecture, source-of-truth rules, public
  interfaces, generation workflow, exclusions, and required gates. The memory
  must not contain temporary progress or copied implementation snippets.

## Rejected alternatives

### Send the full catalog through every Inertia response

Rejected because it increases response size and couples server response
construction to frontend catalog loading. The existing generated-asset flow
already provides a cleaner seam.

### Add `vue-i18n`

Rejected because the application already has a Laravel-backed source and a
small frontend adapter. A second localization framework would add dependency
and format complexity without a current requirement that the deeper existing
module cannot satisfy.

### Maintain separate PHP and TypeScript catalogs

Rejected because duplicated human-edited sources drift and undermine the
centralization goal.

### Use English sentences as keys

Rejected because copy edits become contract changes, context is lost, and
reuse becomes accidental.

### Localize internal diagnostics

Rejected because logs, exceptions, and CLI output serve operators and
developers, require stable search terms, and are outside the user-facing
presentation contract.
