# Profile, Security, and Appearance Refactor Design

## Problem Statement

Profile and Security are currently presented as Settings sub-pages even though
they are primary account-management capabilities. Their HTTP paths, PHP
namespaces, Inertia page names, generated imports, test directories, and shared
layout all retain that Settings ownership. This makes navigation, imports, and
future maintenance misrepresent the domain boundary.

Appearance is also a dedicated Settings page despite being a global preference
that users should be able to change wherever they use the application. Its
current tab control offers Light, Dark, and System modes but cannot be reused
outside that page.

## Solution

Promote Profile and Security into independent account-management capabilities.
They will be served at `/profile` and `/security`, use non-Settings backend,
frontend, generated-code, and test namespaces, and render standalone pages
within the existing application shell. The Settings layout and its navigation
will be removed.

Replace the Appearance page with a reusable `AppearanceToggle` placed in the
application header. The component will use the existing shadcn-vue
`DropdownMenu` radio-group primitives to expose Light, Dark, and System modes.
It will keep using the existing appearance composable, local-storage value,
cookie, document `dark` class, and operating-system change listener.

## User Stories

1. As an authenticated user, I want to open my profile at `/profile`, so that
   my personal information has a direct, recognizable location.
2. As an authenticated user, I want to update my name, email, phone, and avatar
   from the Profile page, so that existing profile-management behavior remains
   available after the refactor.
3. As an authenticated user, I want to remove my avatar from the Profile page,
   so that my avatar-management controls stay with my profile.
4. As a verified user, I want to open account security at `/security`, so that
   password, two-factor authentication, and passkey management have a direct,
   recognizable location.
5. As a user, I want the security page to retain password-confirmation and
   existing feature availability rules, so that the refactor does not weaken
   account protection.
6. As a user, I want to choose Light, Dark, or System appearance from the app
   header, so that I can change the interface preference without visiting a
   dedicated settings page.
7. As a user, I want my appearance choice to persist across subsequent visits,
   so that the application continues to use my preferred theme.
8. As a keyboard or assistive-technology user, I want the appearance choices to
   be presented as an accessible menu with a single selected value, so that all
   three modes can be discovered and selected reliably.
9. As a maintainer, I want PHP controllers and requests, pages, generated
   Wayfinder imports, and feature-test directories to use Profile or Security
   ownership rather than Settings, so that no affected backend or test
   namespace misrepresents the application domain.

## Implementation Decisions

- Register Profile routes in a dedicated profile route file and Security routes
  in a dedicated security route file, both required by the web route entrypoint.
  Remove the Settings route file and all `/settings/*` endpoints; do not retain
  legacy redirects.
- Keep the existing named-route contracts `profile.*`, `security.edit`, and
  `user-password.update` while changing their URLs to `/profile`, `/security`,
  and `/security/password`. Existing code can therefore keep using typed
  Wayfinder route imports while generated URLs reflect the new locations.
- Move `ProfileController`, `AvatarUploadController`, and
  `ProfileUpdateRequest` out of `App\\Http\\Controllers\\Settings` and
  `App\\Http\\Requests\\Settings`; they will use Profile ownership instead.
  Move `SecurityController`, `PasswordUpdateRequest`, and
  `TwoFactorAuthenticationRequest` out of their Settings namespaces and into
  Security ownership. Update every PHP import, route registration, generated
  Wayfinder controller import, and Inertia component reference accordingly;
  no touched backend class may retain `Settings` in its namespace.
- Move Profile and Security Inertia pages out of `pages/settings`, update
  controller component names accordingly, and change the global layout resolver
  so each page receives `AppLayout` without the removed Settings layout.
- Preserve the existing authorization middleware: Profile operations remain
  authenticated; Security remains authenticated, verified, and password
  confirmed for its GET page; password updates retain the six-per-minute throttle.
  Passkey well-known endpoints continue to resolve through `security.edit`.
- Remove the Appearance route, page, and `AppearanceTabs` component. Retain the
  `useAppearance` composable and its Light, Dark, and System persistence and
  system-preference behavior.
- Add a focused, reusable `AppearanceToggle` shared component. Its trigger
  should communicate the active/resolved theme visually and expose an accessible
  label. Its menu must use the repository's shadcn-vue `DropdownMenuRadioGroup`
  and `DropdownMenuRadioItem` primitives; selecting an item delegates to
  `useAppearance().updateAppearance()`.
- Render `AppearanceToggle` from the shared application header so it is
  available throughout authenticated application pages. No new dependency,
  database field, API endpoint, or server-side preference storage is introduced.

## Testing Decisions

- Use existing Pest feature tests as the primary seam: assert the new HTTP URLs,
  named-route behavior, Inertia component names, redirects, and security
  middleware outcomes rather than controller internals.
- Move the profile, avatar-upload, and security feature tests out of
  `tests/Feature/Settings` into their corresponding Profile and Security
  directories. Update imports and Inertia component assertions, preserve all
  current behavior coverage, including avatar ownership and staged-upload error
  handling, and leave no affected test under a `Settings` namespace or folder.
- Add a route-level regression assertion that the removed Appearance route and
  `/settings/*` destinations are unavailable.
- Add frontend coverage at the component seam if the existing JavaScript test
  infrastructure supports it; otherwise verify the frontend contract with
  TypeScript checking and a production Vite build. The important observable
  behavior is that each menu selection calls the existing appearance update path
  and remains selected when the menu reopens.
- Run the focused Profile, avatar, and Security Pest files; regenerate
  Wayfinder definitions; run Pint for PHP changes; then run TypeScript,
  lint/format checks, and the production frontend build.

## Out of Scope

- Changing validation, persistence, media-library behavior, password rules,
  two-factor authentication, passkeys, or email-verification policy.
- Redirecting or otherwise supporting deprecated `/settings/*` URLs.
- Adding an account-preferences database column or synchronizing the appearance
  choice between devices.
- Adding more global navigation redesign beyond removing Settings navigation and
  placing the appearance control in the existing header.

## Further Notes

The user approved a three-state appearance selector, not a binary switch. A
dropdown radio group is the appropriate shadcn-vue control because `Switch`
represents only a boolean state. Existing Tailwind dark-mode handling already
depends on the `dark` class on the document root and must remain unchanged.
