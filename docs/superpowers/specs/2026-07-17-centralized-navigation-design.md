# Centralized Application Navigation Design

**Date:** 2026-07-17
**Status:** Approved
**Classification:** Standard
**Fixed point:** `f8298a1ef6596aaa9b37565845e6cebe1a857e42`

## Problem

Application navigation is defined independently in `AppSidebar.vue` and
`AppHeader.vue`. The definitions have already diverged: Settings is available in
the sidebar but is absent from the header. Adding menu groups in the future
would duplicate translation, authorization, route, icon, and active-state logic
across desktop, mobile, sidebar, and header presentations.

## Goal

Create one typed source of truth for application navigation while preserving
the current user-facing menu structure. Support one level of future menu groups
and render those groups with the interaction pattern best suited to each shell
mode.

## Non-goals

- Do not add an example or placeholder group.
- Do not make navigation database-driven or server-configurable.
- Do not support recursively nested groups.
- Do not change authentication or authorization rules.
- Do not add a new npm dependency.
- Do not change routes, controllers, or generated Wayfinder files.
- Do not redesign unrelated header, sidebar, breadcrumb, search, or user-menu UI.

## Decisions

### Canonical module and adapters

Navigation uses a canonical module with presentation-specific adapters. A
single universal component with a `mode` prop is rejected because it would
concentrate responsive, collapsed, tooltip, dropdown, sheet, and desktop
branches in one shallow module. Server-driven navigation is rejected because
the application has no current requirement for dynamic tenant or database menu
configuration.

The runtime interface is intentionally small:

```ts
const { primary, secondary } = useAppNavigation();
```

Callers do not know how translation, authorization, empty-group filtering, or
active state are implemented.

### Semantic collections

The canonical result exposes semantic collections rather than shell-specific
names:

- `primary`: internal application navigation, including future groups.
- `secondary`: supporting links such as Repository and Documentation.

Each adapter decides where those collections appear. Secondary navigation is
placed in the sidebar footer, the right side of the desktop header, and the
bottom section of the mobile header sheet.

### Typed definitions

Definitions form a discriminated union of leaf items and groups.

Every node has a stable, unique `id`. Renderers must not use translated labels
or array indexes as keys. A leaf owns `href` and cannot own `children`. A group
owns `children`, cannot own `href`, and supports exactly one child level.

Definitions store typed translation keys rather than resolved labels. The
runtime resolver translates them reactively. Internal links use named
Wayfinder imports. External links are marked explicitly rather than inferred
from their URL string.

Visibility is expressed as:

```ts
ability?: keyof Auth['abilities'];
```

Arbitrary visibility callbacks are not supported. This keeps the interface
declarative and prevents navigation configuration from depending on all Inertia
page props.

### Resolution

The canonical definitions pass through a pure resolver which:

1. evaluates the optional ability key;
2. removes unauthorized leaves;
3. removes groups left with no visible children;
4. translates node labels;
5. derives leaf active state using current-or-parent URL matching;
6. marks a group active when any child is active;
7. preserves declaration order and external-link metadata.

`useAppNavigation()` connects this pure resolver to `usePage()`, `useTrans()`,
and `useCurrentUrl()`. The composable returns reactive `primary` and `secondary`
collections. The resolver accepts its dependencies so its complete behavior can
be tested without mounting Vue or mocking Inertia.

## Presentation Strategy

### Sidebar, expanded

Leaf items use `SidebarMenuButton`. A group uses the shadcn-vue `Collapsible`
primitive with `SidebarMenuSub`. A group containing the current page is open by
default. After initialization, user-controlled open state is not continually
overridden by active-state recomputation.

### Sidebar, collapsed icon mode

Leaf items retain the sidebar button's built-in tooltip. A group icon acts as a
`DropdownMenu` trigger so its children remain reachable while submenu content
is visually collapsed.

Hover or keyboard focus displays the translated group label. The tooltip must
not obscure or compete with an open dropdown. Click, Enter, or Space opens the
dropdown. Active styling applies when any child is active.

### Sidebar, mobile

The existing shadcn-vue Sidebar owns its mobile Sheet. Groups use the expanded
`Collapsible` presentation inside that sheet. Selecting a leaf or group child
calls `useSidebar().setOpenMobile(false)`. Toggling a group does not close the
sheet.

### Header, desktop

Top-level leaves use `NavigationMenuLink`. Groups use
`NavigationMenuTrigger` and `NavigationMenuContent`. A group is styled active
when it contains the current page, but its dropdown remains closed until user
interaction.

### Header, mobile

The header owns a controlled `Sheet`. Groups use `Collapsible` inside the
sheet. Selecting a leaf or child emits selection to the owner and closes the
sheet. Toggling a group keeps the sheet open.

### Links and accessibility

Internal navigation uses Inertia `Link`. External navigation uses an anchor
with `target="_blank"` and `rel="noopener noreferrer"`.

Group triggers are labels and interaction controls only; they are never also
navigation links. Labels remain available when an icon is absent. Trigger
state exposes `aria-expanded`, and accessible names use translated labels.
Keyboard navigation, focus management, arrow behavior, and Escape behavior use
the shadcn-vue/Reka primitives rather than custom event emulation.

## Proposed Modules

- `resources/js/types/navigation.ts`: definition and resolved navigation types.
- `resources/js/navigation/app-navigation.ts`: canonical primary and secondary
  definitions.
- `resources/js/lib/navigation.ts`: pure resolver.
- `resources/js/composables/useAppNavigation.ts`: reactive application adapter.
- `resources/js/components/NavMain.vue`: sidebar presentation adapter.
- `resources/js/components/NavFooter.vue`: secondary sidebar adapter.
- `resources/js/components/AppHeaderNavigation.vue`: desktop header adapter.
- `resources/js/components/AppMobileNavigation.vue`: mobile header adapter.
- `resources/js/components/AppSidebar.vue`: sidebar shell/composition only.
- `resources/js/components/AppHeader.vue`: header shell/composition only.

`AppSidebar.vue` and `AppHeader.vue` must no longer import navigation route
helpers, menu icons, menu abilities, or construct menu arrays.

## shadcn-vue Components

The project already contains Sidebar, Navigation Menu, Dropdown Menu, Sheet,
and Tooltip. The shadcn MCP registry identifies `sidebar-05` as the reference
block for collapsible submenus. Only the Collapsible primitive needs to be
added:

```bash
pnpm shadcn-vue add @shadcn/collapsible
```

It uses the already-installed `reka-ui` dependency, so the package manifest and
lockfile should not change.

## Current Menu Migration

The existing menu remains visually ungrouped:

- Primary: Dashboard; Settings when `manage_settings` is true.
- Secondary: Repository; Documentation.

This refactor supplies group capability without inventing a group solely to
demonstrate it.

## Testing Strategy

### Pure resolver tests

Node tests verify translation, stable order, ability filtering, empty-group
removal, active leaf and group derivation, external metadata, immutability, and
the one-level data contract.

### Browser tests

Pest Browser tests verify:

- expanded sidebar leaf and group behavior;
- collapsed sidebar tooltip and dropdown behavior;
- default-open active sidebar group;
- desktop header Navigation Menu behavior;
- mobile header Collapsible behavior;
- mobile sidebar and header sheets close after selecting a leaf or child;
- group triggers do not close mobile sheets;
- keyboard and Escape interactions;
- unauthorized items do not leave empty groups;
- no JavaScript errors or unexpected console logs.

## Acceptance Criteria

1. Application menu content is defined exactly once.
2. Dashboard and Settings appear consistently in both supported shells when
   authorized.
3. Settings is absent in every shell when `manage_settings` is false.
4. Definitions support one group level with no group-to-route ambiguity.
5. Empty groups are removed automatically.
6. Active child state propagates to its group.
7. Each shell uses the approved shadcn-vue strategy for its mode.
8. Collapsed sidebar hover/focus tooltip and group dropdown work without
   interference.
9. Mobile sidebar and header sheets close after selecting a leaf or child.
10. Mobile sheets remain open when only toggling a group.
11. Internal URLs are provided by Wayfinder and external-link behavior is
    explicit.
12. Targeted tests and the repository finishing gate pass.

## Rollback

The change is frontend-only and reversible. Rollback restores local menu arrays
in the two shell components and removes the new navigation modules, adapters,
tests, and Collapsible component. No persisted data, route contract, or backend
schema is affected.
