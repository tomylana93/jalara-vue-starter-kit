# User avatar with Media Library, FilePond, and Cloudflare R2

## Goal

Let an authenticated user upload, replace, and remove their avatar from Profile settings. Show that avatar throughout the authenticated user shell. Use Cloudflare R2 when it is fully configured and local public storage otherwise.

## Scope

- Add a single `avatar` media collection to `User`.
- Integrate the existing generic Vue FilePond uploader into Profile settings.
- Store the final avatar through Spatie Media Library.
- Make the avatar URL available through the shared authenticated-user Inertia prop.
- Render the avatar in the existing shell components, with initials as the no-avatar fallback.
- Add R2-compatible storage configuration and a deterministic local fallback.
- Clean up abandoned staged uploads after 24 hours.

Out of scope: avatar cropping UI, GIF or other file types, admin-managed avatars, private/signed avatar URLs, direct browser-to-R2 uploads, and reusable attachments for other models.

## Accepted files and representation

- Accept only JPEG and WebP source files.
- Limit source files to 2 MB.
- Preserve the original source through Media Library.
- Generate an `avatar` conversion as a 256 x 256 cropped WebP image, synchronously.
- The shared user prop exposes the conversion URL as `avatar`, or `null` when no avatar exists.

## Storage design

Define one application media disk selected by configuration.

- If all required R2 configuration is present, the disk is S3-compatible R2 with a public CDN/custom-domain URL.
- If R2 configuration is incomplete, the disk is Laravel's `public` local disk and URLs use the `storage` symbolic link.
- An R2 upload failure is reported to the caller. It must never silently fall back to local storage after R2 has been selected.
- The `User` avatar collection always uses this selected disk and is `singleFile`, so replacement removes the previous avatar and its conversions.

## Upload and save flow

1. Profile renders the existing generic `Uploader` configured for one JPEG/WebP file up to 2 MB.
2. FilePond immediately sends the file to an authenticated Laravel temporary-upload endpoint.
3. The endpoint validates, stores a temporary file plus a record owned by the current user, and returns its opaque ID and file metadata.
4. The uploader keeps the returned ID in the Profile form state. Removing an unsaved file deletes its temporary upload; this operation is idempotent.
5. Profile Save submits the regular profile attributes and the optional temporary-upload ID.
6. The profile action verifies ownership, moves/promotes the temporary file into `User`'s `avatar` collection, and deletes the temporary record. The new synchronous conversion is available in the redirect response.
7. Avatar removal deletes the user's current media item and redirects back to Profile. The shell then falls back to the user's initials.
8. A scheduled cleanup removes expired temporary uploads and their files after 24 hours.

## Authorization and error handling

- Every temporary-upload, cancellation, promotion, and avatar-removal operation requires authentication and is restricted to the current user's own temporary uploads/avatar.
- Client-side FilePond checks improve feedback only; Laravel repeats type and size validation at the temporary-upload endpoint and promotion boundary.
- Upload endpoint errors are JSON responses FilePond can display. Profile save/removal failures use Laravel/Inertia validation or flash errors.
- Cleanup is safe to rerun. A missing temporary file or already-deleted temporary record does not cause a user-facing failure.

## Frontend integration

- Keep `resources/js/components/uploader/Uploader.vue` generic; do not embed profile or user-specific behavior in it.
- Configure it in `resources/js/pages/settings/Profile.vue` with Wayfinder-generated routes and localized FilePond labels/errors following project conventions.
- Use `auth.user.avatar` in `UserInfo` and the header. Existing initials fallback remains the visual fallback.
- Preserve existing Profile fields, validation errors, and Inertia form behavior.

## Tests

Pest feature tests will cover:

- R2 disk selection when configuration is complete, and local public-disk selection otherwise.
- Authentication and ownership for temporary-upload and removal endpoints.
- Rejection of non-JPEG/WebP files and files over 2 MB.
- Temporary upload, profile save promotion, and avatar URL propagation through the shared user prop.
- Replacing an avatar removes the previous media.
- Removing an avatar restores the `null` shared prop and initials fallback behavior.
- Scheduled cleanup removes records and files older than 24 hours.

Tests use fake storage; no live R2 account or CDN is required.
