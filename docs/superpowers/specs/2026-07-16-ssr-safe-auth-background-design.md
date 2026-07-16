# SSR-safe authentication layout and split-background fallback

## Problem

With Inertia SSR enabled, visiting `/login` during `composer run dev` fails to
render on the SSR server:

```text
SSR ERROR auth/Login
document is not defined
Source: resources/js/composables/useStyleSettings.ts:10:13
```

`AuthLayout.vue` calls `useStyleSettings()`. Its immediate Vue watcher writes
to `document.documentElement`, but the watcher also runs while Vue renders on
Node.js. Inertia then falls back to client-side rendering.

The split authentication layout currently has no bundled background when the
administrator has not uploaded one. The supplied fallback asset exists only in
the sibling `nova-starter-kit` project and must be made local to this project.

## Goal

Render the authentication pages through SSR without browser-global errors and
ensure the split auth layout always receives a local background image URL.

## Approved design

### SSR boundary

`useStyleSettings()` is the single owner of synchronizing Inertia style props
to DOM data attributes. It must return without creating its watcher when
`document` is unavailable. This preserves the existing client behavior:

- after browser hydration, the immediate watcher sets
  `document.documentElement.dataset.theme` and `.font`;
- later Inertia prop changes keep those attributes synchronized;
- SSR performs no DOM mutation and renders the same component tree safely.

The guard belongs in the composable, not `AuthLayout.vue`, because the
composable is reusable by non-auth layouts and callers must not need to know
that it touches a browser API.

### Background fallback

Copy the approved source asset from:

```text
/home/tomylana93/projects/nova-starter-kit/public/assets/images/auth-bg.jpg
```

into this repository at:

```text
public/assets/images/auth-bg.jpg
```

`BrandingResolver` will return a custom media URL when the
`auth_split_background` collection contains media; otherwise it will return
`/assets/images/auth-bg.jpg`. `AuthSplitLayout.vue` continues consuming only
the resolved shared `branding.auth_split_background` prop. It neither knows
nor cares whether the result is uploaded media or the fallback asset.

## Acceptance criteria

1. `composer run dev`, followed by a request to `/login`, emits neither
   `SSR ERROR` nor `document is not defined`.
2. Browser rendering still applies the server-provided `site_theme` and
   `site_font` to the root element on first load and subsequent Inertia visits.
3. When no auth split media exists, the shared branding prop equals
   `/assets/images/auth-bg.jpg` and the split panel uses it as its CSS
   background image.
4. When custom auth split media exists, its resolved media URL remains the
   shared branding prop and wins over the fallback.
5. No route, setting schema, authentication policy, or SSR configuration is
   changed.
6. The copied asset is tracked in this repository; production never reads the
   sibling project.

## Non-goals

- Disabling Inertia SSR.
- Redesigning authentication layouts.
- Changing validation, uploads, media conversions, or style setting values.
- Addressing unrelated historical application-log errors, including R2 test
  failures and missing seeded style settings.

## Risks and mitigations

| Risk | Mitigation |
| --- | --- |
| Guard accidentally prevents client style synchronization | Keep the existing immediate deep watcher unchanged on browser runtimes and test its source contract plus manual SSR smoke test. |
| Fallback masks a custom upload | Resolve custom collection media first, then use the static URL only when it is absent. |
| Asset has an unclear origin at deployment | Copy the source file into `public/assets/images`; do not use a symlink or absolute sibling path. |

## Verification

The primary regression signal is the actual Vite SSR development path:

```bash
composer run dev
# In a second terminal once the server is ready:
curl --fail --silent --show-error http://127.0.0.1:8000/login >/dev/null
```

The first terminal must not report the documented SSR error. The implementation
also adds focused source/contract tests, then runs the repository finishing
gate.

