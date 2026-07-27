# Centralized Application Navigation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use
> superpowers:subagent-driven-development (recommended) or
> superpowers:executing-plans to implement this plan task-by-task. Steps use
> checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace duplicated sidebar and header menu definitions with one typed,
reactive navigation module that supports one-level groups and mode-appropriate
shadcn-vue rendering.

**Architecture:** Static typed definitions are resolved by a pure function, and
`useAppNavigation()` injects translation, abilities, and current-URL state. The
result exposes only `primary` and `secondary`; sidebar, desktop header, and
mobile header remain separate presentation adapters.

**Tech Stack:** Vue 3.5, TypeScript, Inertia v3, Laravel Wayfinder,
shadcn-vue/Reka UI, Tailwind CSS v4, Node test runner, Pest 4 Browser.

## Global Constraints

- Start from fixed point `f8298a1ef6596aaa9b37565845e6cebe1a857e42`.
- Claude is the only implementation writer.
- Work on a task branch/worktree, not directly on `main` or `dev`.
- Preserve the current ungrouped Dashboard, Settings, Repository, and
  Documentation UX.
- Support exactly one group level; do not introduce recursive navigation.
- Use translation keys and named Wayfinder imports; do not hardcode internal
  URLs.
- Use `ability?: keyof Auth['abilities']`; do not introduce arbitrary
  visibility callbacks.
- Do not add a package dependency or edit package manifests.
- Do not change backend routes, controllers, authorization, or generated
  Wayfinder files.
- All user-visible labels and accessible names must be translated.
- A different provider must perform the final read-only review.

---

## File Map

- `resources/js/types/navigation.ts`: definition and resolved-model types.
- `resources/js/navigation/app-navigation.ts`: canonical menu definitions.
- `resources/js/lib/navigation.ts`: pure navigation resolver.
- `resources/js/composables/useAppNavigation.ts`: Inertia/Vue runtime adapter.
- `resources/js/components/NavMain.vue`: expanded, collapsed, and mobile sidebar
  rendering.
- `resources/js/components/NavFooter.vue`: secondary sidebar links.
- `resources/js/components/AppSidebar.vue`: sidebar shell composition.
- `resources/js/components/AppHeaderNavigation.vue`: desktop header navigation.
- `resources/js/components/AppMobileNavigation.vue`: controlled mobile header
  navigation.
- `resources/js/components/AppHeader.vue`: header shell composition.
- `resources/js/components/ui/collapsible/**`: shadcn-vue generated primitive.
- `tests/Frontend/navigation.test.ts`: pure resolver contract tests.
- `tests/Browser/NavigationTest.php`: cross-mode interaction tests.

### Task 1: Establish the typed navigation resolver

**Files:**
- Modify: `resources/js/types/navigation.ts`
- Create: `resources/js/lib/navigation.ts`
- Create: `tests/Frontend/navigation.test.ts`

**Interfaces:**
- Consumes: `Auth['abilities']`, `TranslationKey`, Inertia link href, and
  `LucideIcon`.
- Produces: `NavigationDefinition`, `NavigationNode`,
  `ResolveNavigationOptions`, and `resolveNavigation()`.

- [ ] **Step 1: Write failing resolver tests**

Cover these fixtures with Node `test()` and `node:assert/strict`:

```ts
const definitions: NavigationDefinition[] = [
    {
        type: 'item',
        id: 'dashboard',
        label: 'navigation.dashboard',
        href: '/dashboard',
    },
    {
        type: 'group',
        id: 'settings',
        label: 'settings.sidebar',
        children: [
            {
                type: 'item',
                id: 'general-settings',
                label: 'settings.general.title',
                href: '/settings/general',
                ability: 'manage_settings',
            },
        ],
    },
];
```

Assert translation, input order, ability-true visibility, ability-false
filtering, empty-group removal, leaf active state, parent group active state,
external metadata preservation, and input immutability.

- [ ] **Step 2: Run the test and verify the red state**

```bash
pnpm run test:frontend
```

Expected: failure because `resources/js/lib/navigation.ts` and its exports do
not exist.

- [ ] **Step 3: Add discriminated navigation types**

Define separate leaf/group definition and resolved leaf/group types. A group
must not accept `href`; a leaf must not accept `children`. Derive the ability
key from `Auth['abilities']`. Include stable `id`, translated `label`, optional
icon, explicit external metadata, and resolved `isActive`.

- [ ] **Step 4: Implement the pure resolver**

Use this dependency shape:

```ts
export type ResolveNavigationOptions = {
    abilities: Auth['abilities'];
    translate: (key: TranslationKey) => string;
    isCurrentOrParentUrl: (href: NavigationHref) => boolean;
};

export function resolveNavigation(
    definitions: readonly NavigationDefinition[],
    options: ResolveNavigationOptions,
): NavigationNode[];
```

The resolver must map into new objects, filter unauthorized leaves, remove
empty groups, and derive parent active state with `children.some()`.

- [ ] **Step 5: Run targeted tests and type checking**

```bash
pnpm run test:frontend
pnpm run types:check
```

Expected: all frontend tests pass and Vue TypeScript reports no errors.

- [ ] **Step 6: Commit the resolver task**

```bash
git add resources/js/types/navigation.ts resources/js/lib/navigation.ts tests/Frontend/navigation.test.ts
git commit -m "refactor: add typed navigation resolver"
```

### Task 2: Create the canonical application navigation module

**Files:**
- Create: `resources/js/navigation/app-navigation.ts`
- Create: `resources/js/composables/useAppNavigation.ts`
- Modify: `tests/Frontend/navigation.test.ts`

**Interfaces:**
- Consumes: `resolveNavigation()`, `dashboard()`, `settingsIndex()`,
  `usePage()`, `useTrans()`, and `useCurrentUrl()`.
- Produces: reactive readonly `primary` and `secondary` navigation collections.

- [ ] **Step 1: Add a failing canonical-definition test**

Assert that the exported definition contains these stable IDs in order:

```text
primary: dashboard, settings
secondary: repository, documentation
```

Assert that Settings declares `manage_settings`, internal items use Wayfinder
results, and the secondary items explicitly declare external behavior.

- [ ] **Step 2: Run the frontend tests and verify failure**

```bash
pnpm run test:frontend
```

Expected: failure because the canonical definition does not exist.

- [ ] **Step 3: Implement the canonical definitions**

Move all menu-specific icon imports, translation keys, Wayfinder route imports,
ability metadata, and external URLs to
`resources/js/navigation/app-navigation.ts`. Keep Dashboard and Settings as
top-level leaves; do not create a sample group.

- [ ] **Step 4: Implement `useAppNavigation()`**

Return computed `primary` and `secondary` collections. Connect abilities from
`usePage().props.auth.abilities`, labels from `useTrans()`, and URL matching
from `useCurrentUrl()`. Do not touch browser globals or perform DOM work.

- [ ] **Step 5: Verify the module**

```bash
pnpm run test:frontend
pnpm run types:check
pnpm run test:ssr
```

Expected: all commands pass.

- [ ] **Step 6: Commit the canonical module**

```bash
git add resources/js/navigation/app-navigation.ts resources/js/composables/useAppNavigation.ts tests/Frontend/navigation.test.ts
git commit -m "refactor: centralize application navigation"
```

### Task 3: Adapt the sidebar to the canonical navigation model

**Files:**
- Modify: `resources/js/components/NavMain.vue`
- Modify: `resources/js/components/NavFooter.vue`
- Modify: `resources/js/components/AppSidebar.vue`
- Create: `resources/js/components/ui/collapsible/**`
- Modify: `tests/Frontend/navigation.test.ts`

**Interfaces:**
- Consumes: resolved `NavigationNode[]`, `useSidebar()`, shadcn-vue Sidebar,
  Collapsible, Dropdown Menu, and Tooltip primitives.
- Produces: expanded, collapsed-icon, and mobile sidebar behavior.

- [ ] **Step 1: Add failing source-contract assertions**

Assert that `AppSidebar.vue` imports `useAppNavigation()` and does not import
Dashboard/Settings route helpers, menu icons, `usePage()`, or construct
`mainNavItems`/`footerNavItems`.

- [ ] **Step 2: Run frontend tests and verify failure**

```bash
pnpm run test:frontend
```

Expected: source-contract assertions fail against the current sidebar.

- [ ] **Step 3: Add the official Collapsible primitive**

```bash
pnpm shadcn-vue add @shadcn/collapsible
git diff -- package.json pnpm-lock.yaml
```

Expected: Collapsible component files are created and package manifests remain
unchanged because `reka-ui` is already installed. Stop and report if a package
manifest changes unexpectedly.

- [ ] **Step 4: Implement sidebar leaf behavior**

Render leaf nodes with `SidebarMenuButton`, stable ID keys, translated tooltip,
and resolved active state. Internal links use Inertia `Link`; external links
use secure anchors. On a mobile leaf selection, call
`setOpenMobile(false)`.

- [ ] **Step 5: Implement expanded and mobile group behavior**

Render groups with Collapsible and `SidebarMenuSub`. Initialize the group open
state from `group.isActive` without a watcher that continually forces it open.
Clicking a child closes only the mobile sidebar sheet. Clicking the group
trigger must not close it.

- [ ] **Step 6: Implement collapsed group behavior**

When `useSidebar().state` is `collapsed` and the sidebar is not mobile, render
the group icon as a Dropdown Menu trigger. Preserve the translated tooltip for
hover/focus, hide or suppress it while the dropdown is open, and render each
child inside the dropdown with active styling.

- [ ] **Step 7: Convert the sidebar shell**

Use `primary` and `secondary` from `useAppNavigation()`. Remove all local menu
definitions and their route, icon, translation, page, and ability imports.

- [ ] **Step 8: Verify sidebar changes**

```bash
pnpm run test:frontend
pnpm run types:check
pnpm run lint
```

Expected: all commands pass.

- [ ] **Step 9: Commit the sidebar adapter**

```bash
git add resources/js/components/AppSidebar.vue resources/js/components/NavMain.vue resources/js/components/NavFooter.vue resources/js/components/ui/collapsible tests/Frontend/navigation.test.ts
git commit -m "refactor: adapt sidebar to centralized navigation"
```

### Task 4: Adapt the desktop and mobile header

**Files:**
- Create: `resources/js/components/AppHeaderNavigation.vue`
- Create: `resources/js/components/AppMobileNavigation.vue`
- Modify: `resources/js/components/AppHeader.vue`
- Modify: `tests/Frontend/navigation.test.ts`

**Interfaces:**
- Consumes: resolved `primary`/`secondary`, Navigation Menu, Sheet,
  Collapsible, Tooltip, and Inertia Link.
- Produces: desktop header navigation and controlled mobile header navigation.

- [ ] **Step 1: Add failing header source-contract assertions**

Assert that `AppHeader.vue` imports `useAppNavigation()` and does not import
menu route helpers/icons or declare `mainNavItems`/`rightNavItems`. Assert that
the two presentation adapters exist.

- [ ] **Step 2: Run frontend tests and verify failure**

```bash
pnpm run test:frontend
```

Expected: header source-contract assertions fail.

- [ ] **Step 3: Implement desktop navigation**

Render leaves with `NavigationMenuLink`. Render groups with
`NavigationMenuTrigger` and `NavigationMenuContent`. A group containing the
active route receives active styling but does not default its dropdown open.
Render stable IDs as keys.

- [ ] **Step 4: Implement controlled mobile navigation**

Define a focused adapter which receives navigation nodes and emits one event
when a leaf or child is selected. Render groups with Collapsible. Do not emit
selection from the group trigger.

- [ ] **Step 5: Connect the mobile Sheet owner**

Control the header Sheet with `v-model:open`. Set it false when the mobile
adapter emits leaf selection. Keep secondary links in the sheet's lower
section and close the sheet when one is selected.

- [ ] **Step 6: Reduce `AppHeader.vue` to shell composition**

Use `primary` and `secondary` from `useAppNavigation()`. Preserve appearance,
search, user menu, brand, and breadcrumb behavior unchanged.

- [ ] **Step 7: Verify header changes**

```bash
pnpm run test:frontend
pnpm run types:check
pnpm run lint
pnpm run test:ssr
```

Expected: all commands pass.

- [ ] **Step 8: Commit the header adapters**

```bash
git add resources/js/components/AppHeader.vue resources/js/components/AppHeaderNavigation.vue resources/js/components/AppMobileNavigation.vue tests/Frontend/navigation.test.ts
git commit -m "refactor: adapt header to centralized navigation"
```

### Task 5: Verify cross-mode behavior in a real browser

**Files:**
- Create: `tests/Browser/NavigationTest.php`
- Modify only if required for deterministic selectors:
  `resources/js/components/NavMain.vue`
- Modify only if required for deterministic selectors:
  `resources/js/components/AppHeaderNavigation.vue`
- Modify only if required for deterministic selectors:
  `resources/js/components/AppMobileNavigation.vue`

**Interfaces:**
- Consumes: the completed navigation UI and existing authenticated-user
  factories/authorization setup.
- Produces: regression evidence for every responsive presentation strategy.

- [ ] **Step 1: Create the Pest Browser test file**

```bash
php artisan make:test --pest NavigationTest
mkdir -p tests/Browser
mv tests/Feature/NavigationTest.php tests/Browser/NavigationTest.php
```

Before editing, match the conventions in `tests/Browser/SmokeTest.php` and
`tests/Browser/LocalizationSmokeTest.php`.

- [ ] **Step 2: Write failing expanded-sidebar tests**

Cover visible leaves, authorized Settings, unauthorized Settings, a group
fixture opened through Collapsible, active group default-open, and absence of
empty groups. Every browser page must assert no JavaScript errors and no
unexpected console logs.

- [ ] **Step 3: Write failing collapsed-sidebar tests**

Collapse the sidebar, hover and focus a leaf/group trigger, assert the correct
translated tooltip, open the group dropdown by pointer and keyboard, and verify
the tooltip does not obstruct the open dropdown.

- [ ] **Step 4: Write failing header tests**

At desktop viewport, verify leaf navigation and group Navigation Menu behavior.
At mobile viewport, open the header Sheet, toggle a group without closing the
sheet, then select a child and assert the sheet closes.

- [ ] **Step 5: Write failing mobile-sidebar tests**

At mobile viewport, open the sidebar Sheet, toggle a group without closing it,
select a leaf/child, and assert the sheet closes. Verify Escape closes open
dropdowns and sheets.

- [ ] **Step 6: Add only the selectors needed for robust tests**

Prefer accessible role/name selectors. If they are insufficient, add stable
`data-testid` values derived from node IDs to the three navigation adapters.
Do not expose implementation-only translated labels as selectors.

- [ ] **Step 7: Run the focused browser and frontend suites**

```bash
php artisan test --compact tests/Browser/NavigationTest.php
pnpm run test:frontend
```

Expected: all navigation tests pass.

- [ ] **Step 8: Commit browser coverage**

```bash
git add tests/Browser/NavigationTest.php resources/js/components/NavMain.vue resources/js/components/AppHeaderNavigation.vue resources/js/components/AppMobileNavigation.vue
git commit -m "test: cover centralized navigation modes"
```

### Task 6: Independent review and finishing gate

**Files:**
- Review all files changed since the fixed point.
- Modify only files already owned by this plan when resolving accepted findings.

**Interfaces:**
- Consumes: the fixed implementation diff and reviewer findings.
- Produces: a clean, reviewed diff with complete verification evidence.

- [ ] **Step 1: Confirm scope before review**

```bash
git status --short
git diff --check
git diff f8298a1ef6596aaa9b37565845e6cebe1a857e42 --stat
```

Expected: only files declared in this plan are changed.

- [ ] **Step 2: Obtain an independent read-only review**

The reviewer must be a different provider and must inspect centralization,
typed contracts, authorization, translation, Wayfinder usage, active state,
responsive behavior, accessibility, test coverage, and scope. Findings must
include severity, file/symbol, evidence, impact, and recommended fix.

- [ ] **Step 3: Resolve accepted findings**

For each accepted finding, add or update a failing targeted test, reproduce the
failure, apply the smallest fix, and rerun the targeted test. Do not expand
scope for preference-only findings.

- [ ] **Step 4: Run formatting and targeted tests**

```bash
vendor/bin/pint --dirty --format agent
pnpm run lint
pnpm run format
pnpm run test:frontend
php artisan test --compact tests/Browser/NavigationTest.php
```

Expected: every command passes.

- [ ] **Step 5: Run the canonical full gate**

Wayfinder generation is not required because this plan does not change routes,
controllers, invokable actions, route names, or route parameters.

```bash
composer run agent:gate
```

Expected: lint check, format check, type check, full tests, and frontend build
all pass.

- [ ] **Step 6: Review the final diff**

```bash
git diff --check
git status --short
git diff f8298a1ef6596aaa9b37565845e6cebe1a857e42
```

Confirm that reviewer findings are resolved or explicitly accepted, no package
manifest changed, no unrelated file changed, no Serena memory update is needed,
and no generated route file changed.

- [ ] **Step 7: Prepare the implementation handoff**

Report the final commit, branch/worktree, files and symbols changed, tests and
commands run, passing checks, reviewer disposition, known risks, and ownership
compliance. Do not push or open a pull request unless explicitly requested.
