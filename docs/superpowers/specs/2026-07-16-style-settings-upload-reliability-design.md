# Style Settings Upload Reliability Design

## Goal

Fix four local-upload regressions: oversized Style Settings previews, ICO favicon rejection, invisible staging-validation errors, stale browser favicons after an Inertia save, and missing Profile initials after avatar deletion.

## Evidence and Root Cause

The application stages files locally, stores a `TemporaryUpload` record, and promotes the original into Spatie Media Library only when the parent form is saved. Conversions are queued and consumers fall back to the original during processing.

- `Uploader.vue` defaults its FilePond preview height to 140px; the existing Profile uploader already uses the compact 80px option.
- Style Settings permits only `image/x-icon` in its browser allowlist. Browsers also report ICO as `image/vnd.microsoft.icon`; the Laravel request and `SiteBranding` collection already accept ICO.
- A failed staging request is sent to FilePond via its error callback, but Style Settings renders only its later `*_upload_id` Inertia form error. The immediate `file` validation response has no consumer-level inline presentation.
- The initial favicon link is output by `resources/views/app.blade.php`. An Inertia redirect changes page props without replacing that server-rendered link, so a hot reload is currently needed.
- `Profile.vue` guards the entire Avatar, including `AvatarFallback`, with `v-if="props.avatar"`; after deletion neither the image nor initials mount. The app header already uses the correct unconditional-fallback pattern.

## Scope

### In scope

1. Set every Style Settings uploader to FilePond compact preview mode (80px).
2. Support `image/x-icon` and `image/vnd.microsoft.icon`, plus existing PNG and WebP formats, for favicon selection.
3. Render every immediate uploader validation error beneath its matching Style Settings field.
4. Replace the favicon through a keyed Inertia `<Head>` link after a successful save redirect.
5. Always mount the Profile Avatar fallback while conditionally mounting only its image.
6. Place the Profile avatar control in the right column of the form at large breakpoints, while preserving a stacked layout on smaller screens.
7. Keep every frontend change SSR-safe.
8. Add focused frontend, feature, SSR, and manual browser coverage.

### Out of scope

- Remote storage/CDN migration, synchronous image conversions, queue changes, or new favicon formats.
- Changing the existing staged upload, authorization, cancellation, promotion, or media cleanup behavior.
- Global FilePond restyling outside the shared error event and explicit Style Settings configuration.

## Design

### Shared uploader error contract

`Uploader.vue` emits a typed `upload-error` event with the message already derived by `parseErrorMessage()`. It emits for client MIME/size rejection, non-2xx staging responses, and network errors, while preserving FilePond's native `error(message)` invocation. Style Settings owns a typed `uploadErrors` record keyed by `AssetField`; it clears the field on retry/success and displays `uploadErrors[field]` before the existing `${field}_upload_id` form error.

### Style uploader configuration

Style Settings passes `preview-size="compact"` to the reusable uploader. Its favicon config becomes:

```ts
accept: ['image/png', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'],
maxSize: 1024 * 1024,
```

The backend rules and media collection remain unchanged because they already accept the ICO format and standard MIME variants.

### Reactive favicon

The existing Style Settings `<Head>` gains one stable favicon entry:

```vue
<Head :title="trans('style.title')">
    <link head-key="favicon" rel="icon" :href="branding.favicon" sizes="any" />
</Head>
```

After the update controller redirects back to Style Settings, the new `branding.favicon` prop causes Inertia to replace this keyed link. The Blade link remains the first-request/no-JS fallback. A replacement media item has a distinct URL, so cache-busting query parameters are unnecessary.

### Profile initials fallback

Profile always mounts `<Avatar>` and `<AvatarFallback>{{ avatarInitials }}</AvatarFallback>`. Only `AvatarImage` and the remove link depend on `props.avatar`. This directly aligns Profile with the existing header behavior.

The profile form becomes a responsive two-column grid at the existing large breakpoint: profile fields occupy the primary left column and the avatar control is ordered in the right column. Below that breakpoint, the form remains a single vertical column with the avatar control preceding the text fields. The avatar control keeps its current uploader and remove affordance; only composition and responsive placement change.

### SSR safety

No new reactive state, computed value, or template expression may read `window`, `document`, `File`, `XMLHttpRequest`, or browser-only FilePond APIs during module evaluation or server rendering. The responsive Profile placement uses static Tailwind classes only, and the favicon link is declarative Inertia head markup derived solely from server-provided props. The uploader's browser-only FilePond initialization stays in `onMounted`, as it is today. The existing SSR render smoke suite must render both the Style Settings and Profile page shapes without browser globals.

## Acceptance Criteria

1. Every Style Settings FilePond preview/poster is 80px; no other default preview is changed.
2. Valid local ICO uploads reported as either standard image MIME type stage and save; PNG and WebP continue to work and the 1 MiB cap remains.
3. A local or server-rejected upload shows its specific message below the affected Style Settings uploader before form save.
4. Saving a replacement PNG favicon changes the active `link[rel="icon"]` after the Inertia redirect without Vite hot reload or a browser refresh.
5. After avatar deletion, the Profile page shows the user's initials immediately; its page and header both receive null avatar data.
6. At large breakpoints the avatar control appears to the right of profile fields; at smaller breakpoints it remains stacked and usable.
7. Style Settings and Profile SSR renders complete without browser-global errors.
8. Existing authorization, local staging cleanup, promotion, and queued conversion tests remain green.

## Test Matrix

| Layer | Coverage |
| --- | --- |
| Pest | Favicon ICO MIME variants are accepted; unsupported file fails `file` validation; authorization remains intact. |
| Node frontend | Compact prop, MIME list, error-event binding/error rendering, keyed favicon entry, Profile fallback structure, and responsive grid classes. |
| Browser | Local PNG favicon save updates DOM head, ICO upload succeeds, invalid upload displays error, avatar deletion shows initials without reload, and large/small Profile layouts remain usable. |
| SSR | Extend the existing SSR render smoke fixture/assertions for dynamic favicon and the Profile fallback/layout; it must complete without browser globals. |
| Gate | Existing Style Settings/Profile tests, lint, format, types, tests, and production build. |

## Risks

- Browser ICO MIME reporting differs; accepting both standard values mitigates it and requires browser verification with a real local `.ico` file.
- The installed Vue FilePond adapter's event behavior must be checked before implementation; the component's explicit event remains the consumer contract.
- Favicon markup exists before hydration; use a stable Inertia `head-key` to replace rather than duplicate the tag.
- Tests must not wait for queued conversions; favicon serves its original and avatar already falls back to its original URL.
