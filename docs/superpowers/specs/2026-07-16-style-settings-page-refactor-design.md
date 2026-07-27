# Style Settings Page Refactor

## Problem Statement

The style settings page does not follow the established settings-page presentation used elsewhere in the application. It omits the settings breadcrumb trail, renders configuration choices with native HTML selects instead of the repository's shadcn-vue components, and does not group its content in the card-based layout used by the general settings page. This makes navigation, validation feedback, accessibility state, and visual presentation inconsistent across settings pages.

## Solution

Refactor the style settings page to follow the existing general settings page pattern. The page will expose a `Settings → Style` breadcrumb trail, render each style option through the existing shadcn-vue Select component, connect each select to the existing Inertia form validation state, and group the form content in the repository's card components. Branding uploads and their current behavior remain inside the same form and continue to use the existing uploader component.

## User Stories

1. As an administrator, I want to see where the Style page sits within Settings, so that I can understand the current navigation context.
2. As an administrator, I want the Style page to look and behave like other settings pages, so that managing the application feels consistent.
3. As an administrator, I want each style setting to use the shared select component, so that keyboard and visual interactions are consistent with the rest of the application.
4. As an administrator, I want the current value of every style setting selected when the page opens, so that I can review the active configuration before editing it.
5. As an administrator, I want every style option submitted under its existing field name, so that saving style settings continues to work without backend changes.
6. As an administrator, I want invalid selections to expose their validation state and message, so that I can identify and correct a rejected value.
7. As an administrator, I want branding upload controls to keep their existing upload, removal, and validation behavior, so that the visual refactor does not disrupt asset management.
8. As a keyboard or assistive-technology user, I want each select trigger associated with its label and invalid state, so that the form remains understandable and operable.

## Implementation Decisions

- Use the established settings index route followed by the style edit route for the breadcrumb trail.
- Use the existing translated Settings and Style titles for breadcrumb labels; do not introduce new copy.
- Replace all five native selects—logo style, authentication layout, application layout, theme, and font—with the existing shadcn-vue Select composition.
- Preserve existing field names, option values, option labels, initial values, and update endpoint.
- Bind each select's initial value to the corresponding server-provided style setting.
- Use the Inertia Form validation slot to expose invalid state and trigger field validation on blur, matching the general settings page pattern.
- Use the existing Card components to group the form while retaining a single update form and submit action.
- Keep branding upload state, hidden submission fields, URL resolution, accepted file types, file-size limits, and uploader behavior unchanged.
- Keep the change frontend-only. No route, controller, request validation, settings object, translation, database, authorization, or generated Wayfinder changes are required.
- Do not extract a new reusable field component; the five-field loop remains the smallest appropriate abstraction for this page.

## Testing Decisions

- Test externally observable page behavior rather than shadcn-vue internals.
- Preserve the existing feature tests that prove authorization, Inertia component rendering, current settings props, update persistence, validation, and branding upload behavior.
- Add or update the highest practical frontend-facing seam available in the repository to guard the breadcrumb metadata and absence of native select controls. If the repository has no established Vue component-test seam, use focused static or browser verification rather than introducing a new test framework for this refactor.
- Run the focused style settings tests, frontend linting, formatting, and type checking before the full repository gate.

## Acceptance Criteria

1. The style settings page displays the `Settings → Style` breadcrumb trail through layout metadata.
2. None of the five style configuration fields uses a native HTML select.
3. All five fields use the repository's shadcn-vue Select components and retain their current selected values and submitted names.
4. Select triggers expose validation state and validate on blur consistently with general settings.
5. The page uses the established card-based settings layout without changing upload behavior.
6. Existing style settings and branding tests continue to pass.
7. Frontend lint, format, type, and build checks pass without unrelated changes.

## Out of Scope

- Changing available style options or their translations.
- Changing how style settings are stored, validated, authorized, or applied globally.
- Redesigning or replacing the uploader component.
- Adding previews for themes, fonts, layouts, logos, or authentication layouts.
- Refactoring other settings pages or extracting a shared settings-form abstraction.
- Changing routes, controllers, generated Wayfinder files, dependencies, or database schema.

## Further Notes

The general settings page is the repository's direct prior art for breadcrumbs, shadcn-vue Select usage, validation wiring, and card layout. The refactor should mirror that pattern while preserving the style page's data-driven loop and branding-specific functionality.
