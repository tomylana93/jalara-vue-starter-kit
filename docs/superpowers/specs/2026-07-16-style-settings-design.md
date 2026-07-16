# Style Settings Design

## Goal

Add a complete, administrator-managed Style Settings capability to Jalara,
using the proven option matrix from the sibling `nova-starter-kit` while
integrating branding assets with Spatie Media Library and Jalara's queued image
processing policy.

The feature controls application branding, application and authentication
layouts, color palette, typography, favicon, and an optional image background
for the split authentication layout. It also moves the existing profile-avatar
conversion from synchronous processing to the same queued conversion model.

## Scope

### Included

- Persist the selected logo style, authentication layout, application layout,
  color theme, and font in a `StyleSettings` Spatie Settings class.
- Provide the same choices and defaults as `nova-starter-kit`.
- Let authorized administrators upload, replace, preview, and remove:
  - primary icon;
  - dark-mode icon;
  - primary logo;
  - dark-mode logo;
  - favicon;
  - split-auth background image.
- Manage permanent image assets through Spatie Media Library.
- Use a shared, purpose-aware temporary-upload pipeline for profile avatars and
  Style Settings media.
- Process derived images through queued Laravel jobs.
- Apply persisted branding and style settings consistently during the initial
  Blade render and subsequent Inertia navigation.
- Add English and Indonesian copy following Jalara's localization conventions.
- Preserve the existing avatar output contract while moving its conversion to
  the queue.

### Excluded

- Per-user palettes, fonts, or layouts.
- A second split-auth background for dark mode.
- Interactive cropping, focal-point selection, or image editing.
- Live mutation of the global application shell before Save.
- SVG uploads.
- Reconstructing or approximating Jalara's official logo in CSS or code.
- Document and non-image upload redesign beyond the shared staging boundary.

## Dependencies and Preconditions

- The official Jalara fallback assets must be supplied by the sibling
  `/home/tomylana93/projects/jalara` repository before implementation can be
  completed. Its brand guidelines name the expected primary logo, dark-mode
  logo, square logo, and dedicated favicon assets, but those files are not
  currently present.
- Placeholder Nova assets must not be used.
- Deployments that enable uploads must run a non-`sync` Laravel queue worker and
  monitor failed jobs.
- Public R2 configuration remains optional. The local `public` disk is the
  fallback when R2 is incomplete.

## Settings Contract

`StyleSettings` owns only scalar presentation choices. Its group is `style`.

| Setting | Allowed values | Default |
| --- | --- | --- |
| `site_logo_style` | `icon`, `logo` | `icon` |
| `site_auth_layout` | `simple`, `split`, `card` | `simple` |
| `site_layout` | `sidebar`, `header` | `sidebar` |
| `site_theme` | `zinc`, `slate`, `emerald`, `rose`, `indigo`, `violet`, `cyan`, `orange`, `teal`, `fuchsia` | `zinc` |
| `site_font` | `inter`, `sora-inter`, `plus-jakarta-dm-sans`, `space-grotesk-inter`, `nunito-plus-jakarta` | `inter` |

String-backed enums define the allowed values. Request validation uses those
enums rather than duplicating string lists.

The existing user-controlled `light`, `dark`, or `system` appearance remains
orthogonal to `site_theme`: appearance selects the light/dark mode, while theme
selects the palette used in both modes.

## Media Ownership

Spatie Settings objects are not Eloquent models and therefore cannot own Media
Library collections. A dedicated singleton Eloquent model, `SiteBranding`, owns
the global branding media lifecycle.

It defines these single-file collections:

| Collection | Purpose |
| --- | --- |
| `icon` | Compact primary mark for light surfaces |
| `icon_dark` | Compact alternate mark for dark surfaces |
| `logo` | Full primary logo for light surfaces |
| `logo_dark` | Full alternate logo for dark surfaces |
| `favicon` | Browser favicon |
| `auth_split_background` | Left-panel image for the split auth layout |

Each collection uses the configured public-media disk selector: R2 when every
required public R2 value is configured, otherwise the local `public` disk. The
Media record, not `StyleSettings`, is the source of truth for disk, path, MIME,
size, and generated conversions.

The singleton must be resolved through one application service or action rather
than repeated `firstOrCreate` calls in consumers. Scalar settings and media
ownership remain separate boundaries: `StyleSettings` controls presentation
choices; `SiteBranding` controls asset lifecycle.

## Temporary Upload Architecture

A generic internal `TemporaryUpload` boundary replaces the avatar-only staging
concept. Each staged original records:

- an opaque identifier;
- the owning user;
- a constrained purpose such as `avatar` or `branding`;
- disk and path;
- original filename;
- trusted MIME type and byte size;
- expiry timestamp.

The storage and cleanup mechanics are shared, but HTTP requests remain
resource-specific. Profile endpoints only stage avatar candidates. Style
Settings endpoints only stage branding candidates and require `manage settings`.
The submitted field identifies the intended branding slot, and server-side
validation maps that slot to its exact media constraints. A staging identifier
cannot be promoted across owners, purposes, fields, or expired records.

Staging the original remains synchronous because FilePond needs a stable ID for
Save and cancellation. Derived image processing is the work delegated to
queued jobs.

Abandoned temporary uploads expire after 24 hours. Cancellation and scheduled
cleanup are idempotent: an already absent record or file is treated as a
successful no-op for its owner.

## Upload Validation

FilePond restrictions are feedback only. Laravel validates the actual stored
file at staging and again before promotion.

| Asset | Accepted MIME types | Maximum size | Additional rule |
| --- | --- | ---: | --- |
| Icon and logo variants | PNG, JPEG, WebP | 2 MiB | Preserve aspect ratio and transparency where supported |
| Favicon | PNG, WebP, ICO | 1 MiB | Preserve the original format |
| Split-auth background | JPEG, WebP | 5 MiB | Minimum 1200×800 pixels |
| Profile avatar | JPEG, WebP | Existing 2 MiB limit | Preserve the existing avatar input contract |

Extension checks supplement MIME sniffing but never replace it. SVG is rejected
because it can contain active content. Promotion rechecks ownership, purpose,
expiry, file existence, byte size, and trusted MIME before creating final media.

## Queued Image Processing

The durable project policy in `queued_image_processing` applies:

1. Stage the validated original synchronously.
2. Promote the original into its final Media Library collection when the parent
   form is saved.
3. Dispatch every derived image conversion to Laravel's queue after the
   database commit.
4. Serve the conversion when it exists; otherwise serve the validated original.
5. Keep non-image uploads outside image-conversion jobs.

Conversions are:

| Collection | Conversion |
| --- | --- |
| Profile `avatar` | Keep the existing `avatar` name; WebP, 256×256, `Fit::Crop` |
| Icons | WebP, maximum 512×512, no crop, original aspect ratio, never upscale |
| Logos | WebP, maximum 1600×600, no crop, original aspect ratio, never upscale |
| Split-auth background | WebP, maximum 1920×1920, quality 85, no crop |
| Favicon | No derived conversion; serve the validated original |

Every conversion preserves the source ratio. Icon and logo conversions must not
upscale smaller originals.

Queued conversion failure does not invalidate the saved media. Resolvers check
`hasGeneratedConversion()` and fall back to the original. Failed jobs remain
available to normal Laravel monitoring and retry operations.

## Atomic Update Semantics

Style Settings Save is atomic from the administrator's perspective even though
database and object storage cannot share one native transaction.

The update action must:

1. authorize and validate all scalar values, removal flags, and staging IDs;
2. validate every staged physical file before changing active state;
3. preserve existing media until all replacements are successfully promoted;
4. save scalar settings and final media references in the coordinated operation;
5. remove superseded media only after replacements are established;
6. compensate by deleting newly created final media if a later promotion or
   settings write fails;
7. dispatch conversions only after commit;
8. return field-specific validation errors without partial success.

An explicit Remove action in the form is only committed on Save. Before Save it
can be undone. Removing one light/dark variant never implicitly removes its
counterpart. Removing the split background does not change
`site_auth_layout`; the split panel falls back to zinc. Media Library deletion
removes the original and all conversions.

## Authorization and Security

The existing `manage settings` permission and settings policy boundary govern
Style Settings. No new style-specific permission is introduced.

Authentication, active-user middleware, policy authorization, and the
permission are required for:

- viewing Style Settings;
- staging a branding upload;
- cancelling an owned branding upload;
- updating scalar settings;
- replacing or removing branding media.

Avatar staging remains restricted to the authenticated profile owner. Generic
staging actions must not imply a generic unrestricted upload endpoint.

Opaque IDs are not authorization. Every read, cancellation, validation, and
promotion query constrains both owner and purpose. Rate limiting protects upload
endpoints. Error responses must not reveal another user's staging metadata.

## Resolver and Fallback Precedence

One branding resolver produces the public contract consumed by Blade and
Inertia. For each image collection its precedence is:

1. generated conversion, when the collection defines one and it is ready;
2. validated original media;
3. official Jalara fallback asset.

Dark-mode icon and logo collections fall back to their official dark variants,
not CSS filters. The favicon falls back to Jalara's dedicated favicon. The
split-auth background has no required brand image fallback; an empty collection
renders the existing zinc panel.

The resolver obtains URLs through Media Library and Laravel Filesystem. It does
not concatenate storage paths or assume that current disk configuration matches
the disk recorded by older media.

## Frontend Application

The Settings index adds a Style Settings card beside General Settings. The edit
page follows Jalara's existing Settings layout, localization, Form, validation,
Wayfinder, and component conventions.

The page contains these sections:

1. logo style and primary/dark icon and logo uploaders;
2. application layout and authentication layout;
3. theme and font;
4. favicon;
5. split-auth background.

The background uploader remains available when `site_auth_layout` is `simple`
or `card`, so changing layouts never discards or hides management of the stored
asset.

Uploader state distinguishes existing media, staged media, pending removal,
upload failure, and validation failure. A successful Save resets staged and
removal state. Leaving the page causes the existing FilePond cleanup behavior to
cancel unsaved staging files.

The page previews choices locally, but it does not mutate the global document
theme, font, or layout until Save succeeds. This prevents the Settings shell
from changing underneath the editor and keeps the visible application state
aligned with persisted state.

## Application Consumers

- `AppBrand` is the single branding component. It selects icon or logo from
  `site_logo_style`, chooses the light/dark asset, and displays the persisted
  site name when appropriate.
- `AppLogo`, desktop and mobile headers, sidebars, and all authentication
  layouts delegate branding to `AppBrand`; they do not hard-code a product name
  or duplicate asset-selection logic.
- `AppLayout` selects sidebar or header layout from `site_layout`.
- `AuthLayout` selects simple, split, or card layout from `site_auth_layout`.
- `AuthSplitLayout` renders the background as centered cover with a dark overlay.
  The image panel remains hidden below the existing `lg` breakpoint. Without an
  uploaded image it renders zinc.
- The favicon resolver supplies Blade's favicon link.
- The selected palette is applied with `data-theme`; appearance continues to
  apply light/dark state independently.
- The selected font is applied with `data-font`.

Blade receives the resolved theme, font, favicon, branding, and site name before
the initial page render. Inertia shares the same logical values for subsequent
navigation. This prevents first-paint mismatch and keeps server and client
consumers on one contract.

## Fonts and Themes

The palette CSS ports Nova's ten theme definitions for both light and dark mode.
The current appearance preference remains unchanged.

All supported font families and required weights are downloaded during the Vite
build and served as local application assets. The browser does not insert or
request a third-party runtime stylesheet when `site_font` changes. Each font
option maps to a stable `data-font` stack, with system fallbacks.

This deliberately increases frontend build size in exchange for predictable
rendering, privacy, offline resilience, and eliminating runtime provider
failure.

## Error Handling and Operations

- Upload validation errors are returned as JSON in the shape FilePond expects.
- Parent-form validation and promotion errors are attached to the corresponding
  Style Settings or profile field.
- Missing or already cancelled staging records are idempotent for their owner;
  cross-owner access remains forbidden.
- A conversion that is pending or failed serves the original rather than a
  broken URL.
- Failure to promote any requested asset leaves scalar settings and active media
  unchanged and cleans up partially promoted replacements.
- Queue workers and failed-job monitoring are deployment requirements.
- The scheduled prune command deletes staging records older than 24 hours and
  runs without overlapping.

## Testing Strategy

### Settings and authorization

- Assert every enum value and the Nova-compatible defaults.
- Assert persistence of each scalar field.
- Assert Settings index and edit access for users with `manage settings`.
- Assert denial for guests, inactive users, and authenticated users without the
  permission.
- Assert the same authorization boundary for staging, cancellation, update,
  replacement, and removal.

### Temporary uploads

- Test the allowed MIME, size, and dimension matrix for every purpose.
- Test MIME spoofing, disallowed SVG, oversized files, undersized backgrounds,
  missing physical files, expired records, owner mismatch, purpose mismatch,
  and field mismatch.
- Test idempotent cancellation and 24-hour pruning.
- Test local-public and fully configured R2 disk selection.

### Media lifecycle

- Test successful promotion into each single-file collection.
- Test replacement preserves old media until the new media is ready.
- Test explicit removal, independent light/dark variants, and fallback behavior.
- Force a later promotion to fail and assert compensation, unchanged settings,
  unchanged active media, and no partial success.
- Assert conversion jobs are dispatched after commit rather than executed in the
  web request.
- Assert resolver precedence for ready conversion, pending conversion, failed
  conversion, original, and official fallback.

### Avatar regression

- Assert avatar staging and ownership still work through the generic boundary.
- Assert the `avatar` conversion remains WebP 256×256 with crop semantics.
- Assert it is queued and the shared user prop falls back to the original until
  the conversion exists.

### Frontend and integration

- Assert Style Settings props, option lists, existing-file payloads, and
  generated Wayfinder actions.
- Assert Settings index navigation and localized English/Indonesian copy.
- Assert `AppBrand` selection, layout component selection, palette/font data
  attributes, favicon, and split background/fallback behavior.
- Assert no hard-coded application URLs and no duplicated branding-selection
  logic in shell consumers.
- Assert translation parity and regenerate frontend translation types.
- Run targeted Pest tests, Wayfinder generation when routes/controllers change,
  Pint, ESLint, Prettier, type checking, build, and the repository's full agent
  gate.

## Acceptance Criteria

1. An authorized active user can manage every Nova-compatible Style Settings
   choice and all six branding assets from Jalara's Settings area.
2. Unauthorized, inactive, and unauthenticated users cannot read or mutate
   Style Settings media or scalar values.
3. Theme, font, application layout, authentication layout, favicon, icon/logo,
   and split background apply consistently on first render and Inertia visits.
4. Split background uses centered cover with a dark overlay, is hidden below
   `lg`, persists when another auth layout is selected, and falls back to zinc
   when absent.
5. Official sibling Jalara assets are the only static branding fallbacks.
6. All final images are owned by Spatie Media Library; scalar Style Settings do
   not store media paths.
7. Avatar and Style Settings conversions run through queued jobs. Pending or
   failed conversions serve the validated original.
8. Multi-asset Style Settings updates never expose partial success.
9. Temporary uploads are owner- and purpose-bound, cancellable, and pruned after
   24 hours.
10. All supported fonts are self-hosted build assets; browsers make no runtime
    font-provider request.
11. Targeted tests and the full finishing gate pass with no unrelated changes.

## Implementation Constraint Summary

- Follow sibling Nova's option matrix, not its direct-path media storage.
- Use Spatie Media Library single-file collections on `SiteBranding`.
- Use generic internal staging with resource-specific authorization and rules.
- Use queued conversions and original fallback as specified in
  `queued_image_processing`.
- Preserve the existing avatar conversion name and visual output.
- Reuse `manage settings`; do not add a new permission.
- Use Wayfinder functions rather than hard-coded application URLs.
- Mirror every localization key in English and Indonesian.
- Do not implement until official fallback assets from sibling `jalara` are
  available or the developer explicitly revises that dependency.
