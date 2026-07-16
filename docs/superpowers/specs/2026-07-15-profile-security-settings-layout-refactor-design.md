# Profile and Security Settings Layout Refactor Design

## Summary

Refactor `resources/js/pages/Profile.vue` and `resources/js/pages/Security.vue` to share `resources/js/layouts/settings/Layout.vue` while preserving their existing routes, forms, validation, avatar upload, password update, two-factor authentication, and passkey behavior. Each page uses `resources/js/components/PageWrapper.vue` as its outer page shell. The settings layout owns only account-settings navigation and the constrained content region.

This is a presentation and composition refactor. It must not change authentication behavior or backend contracts.

## Agent Task Packet

```yaml
task:
  id: USER-2026-07-15-profile-security-layout
  title: Refactor profile and security pages onto the shared settings layout
  mode: standard
  source:
    type: user_request
    reference: conversation request dated 2026-07-15

repository:
  base_branch: dev
  working_branch: claude/profile-security-settings-layout
  fixed_point: origin/dev
  clean_worktree_required: true

scope:
  goal: Give the profile and security pages a consistent page shell and account-settings navigation without changing their behavior.
  non_goals:
    - Change profile, avatar, password, two-factor, passkey, authorization, or password-confirmation behavior.
    - Change URLs, named routes, controllers, requests, database schema, dependencies, or global Inertia layout resolution.
    - Move profile or security pages below the /settings URL namespace.
    - Refactor unrelated settings index or general-settings pages.
  allowed_areas:
    - resources/js/pages/Profile.vue
    - resources/js/pages/Security.vue
    - resources/js/layouts/settings/Layout.vue
    - resources/js/components/PageWrapper.vue only if a verified defect prevents the required composition
    - lang/en/settings.php
    - lang/id/settings.php
    - focused tests for profile and security presentation contracts
  forbidden_areas:
    - app backend behavior
    - routes
    - migrations and database data
    - package manifests and lockfiles
    - Fortify configuration

acceptance_criteria:
  - Profile.vue renders PageWrapper as its outer page shell and SettingsLayout inside it.
  - Security.vue renders PageWrapper as its outer page shell and SettingsLayout inside it.
  - Security.vue keeps password, two-factor, and passkey sections together within the SettingsLayout content slot.
  - SettingsLayout renders type-safe Wayfinder links to profile.edit and security.edit.
  - SettingsLayout exposes an accessible navigation label and marks the active destination with aria-current=page.
  - SettingsLayout stacks navigation above content on small screens and renders sidebar navigation beside content on large screens.
  - PageWrapper owns each page's visible h1, description, outer padding, and page-level spacing.
  - SettingsLayout does not duplicate PageWrapper headings or outer page padding.
  - Existing profile, avatar, email-verification, password, two-factor, and passkey behavior remains unchanged.
  - Existing /profile and /security URLs, named routes, and breadcrumbs remain unchanged.
  - English and Indonesian translations exist for the shared account-settings page copy and navigation.
  - Focused tests and the full agent gate pass.

risk:
  score: 3
  factors:
    ambiguity: 0
    blast_radius: 1
    data_schema: 0
    security_auth: 1
    behavior: 1
    reversibility: 0
  critical_overrides: []

routing:
  orchestrator: openai
  writer: anthropic
  reviewer: google
  fallback_writer: google

context:
  required_skills:
    - jalara-agentic-workflow
    - inertia-vue-development
    - fortify-development
    - wayfinder-development
    - tailwindcss-development
    - pest-testing
  required_mcp:
    - laravel_boost.application_info
    - laravel_boost.search_docs
  documentation_queries:
    - persistent layouts nested layouts Vue
    - Form component reset on success error bags
    - update profile information update password routes
    - Wayfinder Vue Form named routes
    - browser testing Vue pages JavaScript errors
    - responsive layout navigation accessibility
  relevant_existing_patterns:
    - resources/js/pages/settings/Index.vue
    - resources/js/pages/settings/general/Edit.vue
    - resources/js/components/PageWrapper.vue
    - resources/js/layouts/AppLayout.vue

verification:
  targeted_tests:
    - php artisan test --compact tests/Feature/Profile/ProfileUpdateTest.php
    - php artisan test --compact tests/Feature/Security/SecurityTest.php
    - pnpm run lint:check
    - pnpm run types:check
    - pnpm run format:check
  required_gates:
    - composer agent:gate
    - independent read-only review by a non-Anthropic provider
  manual_checks:
    - Verify profile and security navigation at mobile and large desktop widths.
    - Verify active navigation state and keyboard focus in light and dark modes.
    - Verify profile save, password save, 2FA controls, and passkey controls remain usable.

approvals:
  plan_required: true
  migration_required: false
  dependency_change_required: false
  merge_required: true
```

## Current State and Defects

`Profile.vue` and `Security.vue` currently render their content directly inside the global `AppLayout`. They use local small headings and visually hidden page headings, but they do not use the standard `PageWrapper` used by the settings index and general-settings page.

The untracked `settings/Layout.vue` already expresses the intended navigation boundary, but it is not ready for use:

- It duplicates page-shell responsibilities by rendering a page heading and outer padding.
- It references translation keys that do not exist in `lang/en/settings.php` or `lang/id/settings.php`.
- It attempts to render `item.icon` even though neither navigation item defines an icon.
- It uses sibling spacing utilities where this repository prefers `gap-*` composition.
- It provides a visual active state but no `aria-current` state.

## Chosen Architecture

Use explicit page composition rather than changing the application's Inertia layout resolver:

```text
AppLayout (selected globally by resources/js/app.ts)
└── PageWrapper (page heading, description, padding)
    └── SettingsLayout (account navigation and content width)
        └── Page-specific sections and forms
```

`PageWrapper` remains a general page-shell component. It receives translated `title` and `description` props from each page and renders the sole visible page-level `h1`.

`SettingsLayout` remains a local structural component. It renders Profile and Security navigation using Wayfinder route functions, exposes one default slot, and provides responsive navigation/content positioning. It must not render another page title or apply outer page padding.

`Profile.vue` retains the avatar and profile form as page-specific content. `Security.vue` retains all three security concerns—password update, two-factor authentication, and passkeys—as sibling sections in the shared layout content region. Keeping them together preserves the established `/security` information architecture and avoids introducing new routes or navigation states.

## Component Responsibilities

### `PageWrapper.vue`

- Own the page's visible `h1`, optional description, actions area, responsive horizontal padding, and page-level vertical spacing.
- Continue supporting `class` and `contentClass` overrides.
- Do not add account-settings-specific knowledge.
- Change this component only if implementation proves that its existing public interface cannot support the approved composition. No such change is expected from current inspection.

### `layouts/settings/Layout.vue`

- Build translated Profile and Security navigation items.
- Use `editProfile()` and `editSecurity()` Wayfinder results directly with Inertia `Link`.
- Use `toUrl()` only for stable keys and current-location comparison when required by existing composables.
- Render the current item with the existing visual treatment plus `aria-current="page"`; non-current items omit the attribute.
- Use a full-width stacked layout below the large breakpoint and a fixed-width navigation column beside a constrained content column at the large breakpoint.
- Render a separator between navigation and content only in the stacked layout.
- Render one default slot and no page heading.
- Do not render undefined icons.

### `Profile.vue`

- Keep the existing breadcrumb, `<Head>`, user props, avatar state, upload/delete endpoints, form action, precognitive validation timing, deferred email validation, verification prompt, field names, autocomplete values, and test hooks.
- Replace the visually hidden page heading and direct root content wrapper with `PageWrapper` and `SettingsLayout` composition.
- Use translated page title and description for `<Head>` and `PageWrapper`.
- Preserve the small Profile section heading only if needed to distinguish the form section from the page heading; avoid duplicate visible copy. The implementation should prefer a concise section heading such as “Profile information.”

### `Security.vue`

- Keep the existing breadcrumb, `<Head>`, props, password form action, preserve-scroll behavior, password field reset rules, password rules, field names, autocomplete values, test hooks, 2FA props, and passkey props.
- Replace the visually hidden page heading and fragmented direct-root sections with one `PageWrapper` containing one `SettingsLayout`.
- Place password, `ManageTwoFactor`, and `ManagePasskeys` inside a single vertical section stack with the existing separation between concerns.
- Use translated page title and description for `<Head>` and `PageWrapper`.

## Copy and Localization

Add matching keys to `lang/en/settings.php` and `lang/id/settings.php`. The exact key structure is:

```php
'layout' => [
    'aria' => 'Account settings',
],
'nav' => [
    'profile' => 'Profile',
    'security' => 'Security',
],
'profile' => [
    'title' => 'Profile settings',
    'heading' => 'Profile settings',
    'description' => 'Update your profile information and avatar.',
],
'security' => [
    'title' => 'Security settings',
    'heading' => 'Security settings',
    'description' => 'Manage your password and sign-in security.',
],
```

The Indonesian file must provide natural Indonesian equivalents with the identical key structure. Existing field-level English copy may remain unchanged in this scoped refactor; translating every profile and security control is a separate concern.

## Accessibility and Responsive Behavior

- Each page has exactly one visible `h1`, rendered by `PageWrapper`.
- Existing form labels remain explicitly associated with their controls.
- The account-settings `<nav>` has a translated `aria-label`.
- The active navigation link has `aria-current="page"` in addition to its visual background.
- Links remain Inertia links with Wayfinder route objects; keyboard navigation and focus styling provided by the existing Button/Link composition must remain intact.
- At small widths, navigation appears before content and uses the available width. At `lg` and above, navigation and content appear side by side.
- Existing light and dark theme behavior must not regress.

## Data Flow and Error Handling

No data-flow changes are permitted. Profile updates continue through `ProfileController.update.form()`. Avatar upload and removal continue through their existing Wayfinder actions. Password updates continue through `SecurityController.update.form()`. Two-factor and passkey child components retain their existing actions and event handling.

Inertia validation errors remain rendered beside their existing fields. Password fields retain their current success/error reset behavior. The refactor must not add optimistic updates, new local state, new error bags, or new redirects.

## Testing Strategy

Backend behavior is already covered by `ProfileUpdateTest`, `AvatarUploadTest`, and `SecurityTest`. Preserve those tests and add the smallest focused presentation-contract coverage that fails before the refactor and passes after it. The preferred seam is a source-contract test consistent with the existing profile email-validation contract, asserting that both pages import and compose `PageWrapper` and `SettingsLayout`, and that the settings layout uses both Wayfinder destinations and an accessible current-page state.

Do not duplicate all backend behavior in browser tests. If the repository's browser-test environment is stable, add or extend one smoke test that visits `/profile` and `/security` as an authenticated user and asserts no JavaScript errors. Otherwise, rely on the existing feature tests plus frontend static checks and document the manual responsive check.

## Implementation Constraints for Claude

- Start from a clean feature branch descending from `dev`; do not implement on `dev` or `main`.
- Treat the current untracked `resources/js/layouts/settings/Layout.vue` as design input, but ensure it is intentionally added on the implementation branch.
- Claude is the only writer. Research and review agents are read-only.
- Inspect sibling components before editing and do not introduce dependencies or new top-level directories.
- Use `apply_patch` for edits and preserve unrelated worktree changes.
- Run targeted checks during the refactor, then `composer agent:gate` before handing the fixed-point diff to the Google reviewer.
- Do not expand scope in response to unrelated lint, formatting, or test findings.

## Completion Evidence

The implementation handoff must list the fixed-point commit, files changed, documentation consulted, targeted tests and gates with their results, manual checks performed, and independent-review findings. Completion requires acceptance-criteria evidence, a passing `composer agent:gate`, and a read-only review from a non-Anthropic provider.
