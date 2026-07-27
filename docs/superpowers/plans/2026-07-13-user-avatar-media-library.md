# User Avatar Media Library Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let authenticated users manage a JPEG/WebP avatar from Profile settings, store it on Cloudflare R2 when configured (otherwise local public storage), and show it throughout the user shell.

**Architecture:** A small `MediaDisk` module determines the final storage disk from R2 configuration. `User` owns a single Media Library avatar collection. FilePond stages an upload through authenticated Laravel endpoints; saving Profile promotes the owned staged file to the collection, and a scheduled command removes abandoned staging files after 24 hours.

**Tech Stack:** Laravel 13, PHP 8.5, Spatie Media Library 11.23, Pest 4, Inertia 3, Vue 3, Wayfinder, FilePond, Cloudflare R2 (S3-compatible), Tailwind CSS 4.

## Global Constraints

- Preserve unrelated, uncommitted changes already present in `AGENTS.md`, `CLAUDE.md`, Composer/PNPM lockfiles, `config/media-library.php`, the Media Library migration, and `resources/js/components/uploader/`.
- Do not add dependencies.
- Accept only `image/jpeg` and `image/webp`, with a 2 MiB (`2 * 1024 * 1024`) maximum source size.
- R2 is selected only when all required R2 settings are non-empty. Once selected, an R2 error is returned; never write a local fallback copy.
- The final avatar URL is public (R2 custom-domain/CDN URL, or the local `public` disk URL) and uses the synchronous `avatar` conversion: cropped 256 × 256 WebP.
- All avatar endpoints require authentication and operate only on the current user's staging records/avatar.
- Use Wayfinder route functions in Vue; never hardcode application URLs.
- Create/update Pest tests before implementation code for every behavior, run only the affected tests during each task, and run Pint after PHP changes.

---

## File map

| File | Responsibility |
| --- | --- |
| `app/Support/MediaDisk.php` | Hide R2-completeness detection and expose the selected disk name. |
| `app/Models/User.php` | Define the avatar collection/conversion and provide the avatar URL. |
| `app/Models/TemporaryAvatarUpload.php` | Persist an authenticated user's staged upload and its expiry. |
| `app/Actions/PromoteTemporaryAvatarUpload.php` | Atomically validate ownership, move a staged file into Media Library, and remove the staging record. |
| `app/Http/Requests/StoreTemporaryAvatarUploadRequest.php` | Enforce the server-side source file constraints. |
| `app/Http/Requests/Settings/ProfileUpdateRequest.php` | Validate the optional staged-upload UUID submitted with Profile. |
| `app/Http/Controllers/Settings/AvatarUploadController.php` | Return FilePond-compatible JSON for store/destroy of staged uploads. |
| `app/Http/Controllers/Settings/ProfileController.php` | Expose the existing avatar, promote an optional staged upload on Save, and delete the final avatar. |
| `app/Console/Commands/PurgeExpiredTemporaryAvatarUploads.php` | Delete expired staged objects and records safely. |
| `config/filesystems.php`, `.env.example` | Define R2 and the explicit local/R2 media disks. |
| `config/media-library.php` | Set global Media Library safeguards; retain the package migration already in the working tree. |
| `database/migrations/2026_07_13_070000_create_temporary_avatar_uploads_table.php` | Store staging metadata and expiry. |
| `routes/settings.php`, `routes/console.php` | Register authenticated avatar routes and daily cleanup. |
| `app/Http/Middleware/HandleInertiaRequests.php` | Add the public conversion URL to `auth.user.avatar`. |
| `resources/js/pages/settings/Profile.vue` | Bind FilePond staging IDs to Profile Save and add final-avatar removal. |
| `resources/js/types/auth.ts` | Make `avatar` nullable and keep the shared user contract precise. |
| `tests/Unit/Support/MediaDiskTest.php` | Test deterministic R2/local disk selection. |
| `tests/Feature/Settings/AvatarUploadTest.php` | Test upload authorization, validation, promotion, replacement/removal, shared prop, and cleanup. |

### Task 1: Establish Media Library avatar storage and model contract

**Files:**
- Create: `app/Support/MediaDisk.php`
- Modify: `config/filesystems.php`
- Modify: `.env.example`
- Modify: `config/media-library.php`
- Modify: `app/Models/User.php`
- Modify: `tests/Unit/Models/UserTest.php`
- Create: `tests/Unit/Support/MediaDiskTest.php`

**Interfaces:**
- Produces `MediaDisk::avatar(): string`, returning `r2` only when `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_ENDPOINT`, and `R2_PUBLIC_URL` are all non-empty; otherwise returning `public`.
- Produces `User::avatarUrl(): ?string` and the Media Library collection `avatar` with conversion `avatar`.
- Consumes the existing unpublished `media` migration and installed `spatie/laravel-medialibrary` package; do not recreate the package migration.

- [ ] **Step 1: Write failing unit tests for disk selection and the User avatar contract.**

  Add these tests, using `config()->set()` so no real R2 credentials are needed:

  ```php
  use App\Models\User;
  use App\Support\MediaDisk;

  test('avatar media uses the public disk when R2 is incomplete', function () {
      config()->set('filesystems.disks.r2', [
          'key' => null,
          'secret' => null,
          'bucket' => null,
          'endpoint' => null,
          'url' => null,
      ]);

      expect(MediaDisk::avatar())->toBe('public');
  });

  test('avatar media uses R2 only when every required setting is present', function () {
      config()->set('filesystems.disks.r2', [
          'key' => 'key',
          'secret' => 'secret',
          'bucket' => 'bucket',
          'endpoint' => 'https://account.r2.cloudflarestorage.com',
          'url' => 'https://cdn.example.test',
      ]);

      expect(MediaDisk::avatar())->toBe('r2');
  });

  test('a user avatar collection is single-file and exposes its conversion URL', function () {
      Storage::fake('public');
      $user = User::factory()->create();

      $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))
          ->toMediaCollection('avatar');

      expect($user->getMedia('avatar'))->toHaveCount(1)
          ->and($user->avatarUrl())->toBe($user->getFirstMediaUrl('avatar', 'avatar'));
  });
  ```

  Import `Illuminate\Http\UploadedFile` and `Illuminate\Support\Facades\Storage` in the affected test files. Use the repository's existing Pest conventions.

- [ ] **Step 2: Run the focused tests and confirm they fail.**

  Run: `php artisan test --compact tests/Unit/Support/MediaDiskTest.php tests/Unit/Models/UserTest.php`

  Expected: FAIL because `MediaDisk` and the User media methods do not exist.

- [ ] **Step 3: Add the disk boundary and R2 configuration.**

  Create `app/Support/MediaDisk.php`:

  ```php
  <?php

  namespace App\Support;

  final class MediaDisk
  {
      public static function avatar(): string
      {
          $r2 = config('filesystems.disks.r2');

          if (! is_array($r2)) {
              return 'public';
          }

          foreach (['key', 'secret', 'bucket', 'endpoint', 'url'] as $key) {
              if (blank($r2[$key] ?? null)) {
                  return 'public';
              }
          }

          return 'r2';
      }
  }
  ```

  In `config/filesystems.php`, add an `r2` S3 disk with these exact environment mappings:

  ```php
  'r2' => [
      'driver' => 's3',
      'key' => env('R2_ACCESS_KEY_ID'),
      'secret' => env('R2_SECRET_ACCESS_KEY'),
      'region' => env('R2_REGION', 'auto'),
      'bucket' => env('R2_BUCKET'),
      'url' => env('R2_PUBLIC_URL'),
      'endpoint' => env('R2_ENDPOINT'),
      'use_path_style_endpoint' => env('R2_USE_PATH_STYLE_ENDPOINT', true),
      'throw' => true,
      'report' => true,
  ],
  ```

  Add the corresponding blank R2 variables to `.env.example`. Retain the existing generic `s3` disk and AWS variables because other application features may use them. In `config/media-library.php`, set `max_file_size` to `2 * 1024 * 1024`, restrict `allowed_extensions` to `['jpg', 'jpeg', 'webp']`, and remove the unused `Spatie\\MediaLibraryPro\\Models\\TemporaryUpload` import and Pro-only temporary-upload config entries. Do not set an R2 default disk there; the collection selects its disk through `MediaDisk`.

- [ ] **Step 4: Make User own the final avatar.**

  Update `User` to implement `HasMedia`, use `InteractsWithMedia`, and add these methods (with their imports):

  ```php
  public function registerMediaCollections(): void
  {
      $this->addMediaCollection('avatar')
          ->singleFile()
          ->useDisk(MediaDisk::avatar())
          ->acceptsMimeTypes(['image/jpeg', 'image/webp']);
  }

  public function registerMediaConversions(?Media $media = null): void
  {
      $this->addMediaConversion('avatar')
          ->format('webp')
          ->fit(Fit::Crop, 256, 256)
          ->performOnCollections('avatar')
          ->nonQueued();
  }

  public function avatarUrl(): ?string
  {
      $url = $this->getFirstMediaUrl('avatar', 'avatar');

      return $url === '' ? null : $url;
  }
  ```

  Use `Spatie\Image\Enums\Fit` and `Spatie\MediaLibrary\MediaCollections\Models\Media`. Keep the existing role-protection traits and methods intact.

- [ ] **Step 5: Run formatting and focused tests.**

  Run:

  ```bash
  vendor/bin/pint --dirty --format agent
  php artisan test --compact tests/Unit/Support/MediaDiskTest.php tests/Unit/Models/UserTest.php
  ```

  Expected: PASS. The tests prove the fallback is configuration-based and the avatar collection produces the selected conversion.

- [ ] **Step 6: Commit the foundation.**

  ```bash
  git add app/Support/MediaDisk.php app/Models/User.php config/filesystems.php config/media-library.php .env.example tests/Unit/Support/MediaDiskTest.php tests/Unit/Models/UserTest.php
  git commit -m "feat: configure user avatar media storage"
  ```

### Task 2: Add owned staged-upload endpoints and promotion action

**Files:**
- Create: `database/migrations/2026_07_13_070000_create_temporary_avatar_uploads_table.php`
- Create: `app/Models/TemporaryAvatarUpload.php`
- Create: `app/Http/Requests/StoreTemporaryAvatarUploadRequest.php`
- Modify: `app/Http/Requests/Settings/ProfileUpdateRequest.php`
- Create: `app/Actions/PromoteTemporaryAvatarUpload.php`
- Create: `app/Http/Controllers/Settings/AvatarUploadController.php`
- Modify: `app/Http/Controllers/Settings/ProfileController.php`
- Modify: `routes/settings.php`
- Create: `tests/Feature/Settings/AvatarUploadTest.php`

**Interfaces:**
- Produces `POST profile.avatar-uploads.store` with a `file` multipart field and JSON `{ id, name, size, type }`.
- Produces `DELETE profile.avatar-uploads.destroy` with a `TemporaryAvatarUpload` route model; a non-owner receives 404.
- Produces `DELETE profile.avatar.destroy`, which clears only the current user's `avatar` collection.
- Produces `PromoteTemporaryAvatarUpload::handle(User $user, ?string $temporaryAvatarUploadId): void`.
- `ProfileUpdateRequest::temporaryAvatarUploadId(): ?string` returns the validated UUID or `null`.

- [ ] **Step 1: Write failing feature tests for staging, validation, ownership, promotion, replacement, and final deletion.**

  Start `AvatarUploadTest.php` with `Storage::fake('public')` in `beforeEach`. Cover these executable cases:

  ```php
  test('an authenticated user can stage a JPEG avatar', function () {
      $user = User::factory()->create();

      $this->actingAs($user)
          ->postJson(route('profile.avatar-uploads.store'), [
              'file' => UploadedFile::fake()->image('avatar.jpg'),
          ])
          ->assertCreated()
          ->assertJsonStructure(['id', 'name', 'size', 'type']);

      expect(TemporaryAvatarUpload::query()->where('user_id', $user->id)->count())->toBe(1);
  });

  test('staged avatars reject unsupported types and files larger than two MiB', function () {
      $user = User::factory()->create();

      $this->actingAs($user)
          ->postJson(route('profile.avatar-uploads.store'), ['file' => UploadedFile::fake()->create('avatar.png', 100, 'image/png')])
          ->assertUnprocessable()
          ->assertJsonValidationErrors('file');

      $this->actingAs($user)
          ->postJson(route('profile.avatar-uploads.store'), ['file' => UploadedFile::fake()->create('avatar.webp', 2049, 'image/webp')])
          ->assertUnprocessable()
          ->assertJsonValidationErrors('file');
  });

  test('a profile save promotes only the current users staged avatar', function () {
      $user = User::factory()->create();
      $upload = stageAvatarFor($user);

      $this->actingAs($user)
          ->patch(route('profile.update'), profilePayload($user, ['temporary_avatar_upload_id' => $upload->id]))
          ->assertRedirect(route('profile.edit'));

      expect($user->refresh()->getMedia('avatar'))->toHaveCount(1)
          ->and(TemporaryAvatarUpload::find($upload->id))->toBeNull();
  });

  test('a user cannot destroy or promote another users staged avatar', function () {
      $owner = User::factory()->create();
      $attacker = User::factory()->create();
      $upload = stageAvatarFor($owner);

      $this->actingAs($attacker)
          ->deleteJson(route('profile.avatar-uploads.destroy', $upload))
          ->assertNotFound();

      $this->actingAs($attacker)
          ->patch(route('profile.update'), profilePayload($attacker, ['temporary_avatar_upload_id' => $upload->id]))
          ->assertSessionHasErrors('temporary_avatar_upload_id');
  });

  test('a new avatar replaces the old avatar and the owner can remove it', function () {
      $user = User::factory()->create();
      $user->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('avatar');
      $upload = stageAvatarFor($user);

      $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, ['temporary_avatar_upload_id' => $upload->id]));
      expect($user->refresh()->getMedia('avatar'))->toHaveCount(1);

      $this->actingAs($user)
          ->delete(route('profile.avatar.destroy'))
          ->assertRedirect(route('profile.edit'));
      expect($user->refresh()->hasMedia('avatar'))->toBeFalse();
  });
  ```

  Define `stageAvatarFor()` and `profilePayload()` as local test helpers at the bottom of the same test file so every request has valid existing Profile fields. Add unauthenticated `401/302` cases matching the repository's route convention.

- [ ] **Step 2: Run the new feature test and confirm it fails.**

  Run: `php artisan test --compact tests/Feature/Settings/AvatarUploadTest.php`

  Expected: FAIL because the model, routes, action, and controllers do not yet exist.

- [ ] **Step 3: Create the staging persistence and validation boundary.**

  Generate the migration and model with Artisan (`--no-interaction`). The migration must create:

  ```php
  $table->uuid('id')->primary();
  $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
  $table->string('disk');
  $table->string('path');
  $table->string('original_name');
  $table->string('mime_type');
  $table->unsignedBigInteger('size');
  $table->timestamp('expires_at')->index();
  $table->timestamps();
  ```

  Implement `TemporaryAvatarUpload` using `HasFactory` and `HasUuids`, with a `user(): BelongsTo` relationship, `$guarded = []`, and casts for `size` (`integer`) and `expires_at` (`immutable_datetime`).

  Implement `StoreTemporaryAvatarUploadRequest` with:

  ```php
  public function rules(): array
  {
      return [
          'file' => [
              'required',
              File::types(['jpeg', 'webp'])->max(2 * 1024),
          ],
      ];
  }
  ```

  Also add `temporary_avatar_upload_id => ['nullable', 'uuid']` to the Profile request rules and return the nullable string from `temporaryAvatarUploadId()`.

- [ ] **Step 4: Implement staging, promotion, and final removal.**

  `AvatarUploadController::store()` must select `MediaDisk::avatar()`, store the validated `UploadedFile` under `temporary-avatars/{user UUID}`, persist a record expiring at `now()->addDay()`, and return:

  ```php
  return response()->json([
      'id' => $upload->id,
      'name' => $upload->original_name,
      'size' => $upload->size,
      'type' => $upload->mime_type,
  ], 201);
  ```

  `destroy()` must scope the route-model record to `$request->user()->id`, delete the object if present, delete the record, and return `204`. Use `abort_unless()` or `firstOrFail()` so another user's ID returns 404.

  `PromoteTemporaryAvatarUpload` must look up the ID scoped to the supplied user, throw a `ValidationException` keyed `temporary_avatar_upload_id` when missing/expired, call:

  ```php
  $user->addMediaFromDisk($upload->path, $upload->disk)
      ->usingFileName($upload->original_name)
      ->toMediaCollection('avatar');
  ```

  Then delete the staging record. Do not call `Storage::delete()` after `addMediaFromDisk()` because Media Library owns the move. Delete the record only after promotion succeeds.

  In `ProfileController::update()`, retain the existing `UpdateUserProfile` call, then call the promotion action with the current user and request ID. Add `destroyAvatar()` to clear `avatar`, flash a success message, and redirect to `profile.edit`.

  Register exactly these authenticated routes in `routes/settings.php` before the generic Profile update route:

  ```php
  Route::post('settings/profile/avatar-uploads', [AvatarUploadController::class, 'store'])->name('profile.avatar-uploads.store');
  Route::delete('settings/profile/avatar-uploads/{temporaryAvatarUpload}', [AvatarUploadController::class, 'destroy'])->name('profile.avatar-uploads.destroy');
  Route::delete('settings/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
  ```

- [ ] **Step 5: Run migration, formatter, and feature tests.**

  Run:

  ```bash
  php artisan migrate --no-interaction
  vendor/bin/pint --dirty --format agent
  php artisan test --compact tests/Feature/Settings/AvatarUploadTest.php tests/Feature/Settings/ProfileUpdateTest.php
  ```

  Expected: PASS. The current Profile tests remain green because the staged ID is optional.

- [ ] **Step 6: Commit the staging workflow.**

  ```bash
  git add app/Actions/PromoteTemporaryAvatarUpload.php app/Http/Controllers/Settings/AvatarUploadController.php app/Http/Controllers/Settings/ProfileController.php app/Http/Requests/StoreTemporaryAvatarUploadRequest.php app/Http/Requests/Settings/ProfileUpdateRequest.php app/Models/TemporaryAvatarUpload.php database/migrations routes/settings.php tests/Feature/Settings/AvatarUploadTest.php
  git commit -m "feat: stage and promote user avatar uploads"
  ```

### Task 3: Share and manage the avatar in the Inertia UI

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `app/Http/Controllers/Settings/ProfileController.php`
- Modify: `resources/js/types/auth.ts`
- Modify: `resources/js/pages/settings/Profile.vue`
- Modify: `tests/Feature/Settings/AvatarUploadTest.php`
- Modify: `resources/js/components/uploader/Uploader.vue` only if needed to correctly emit one staged ID or surface FilePond errors; keep it generic.

**Interfaces:**
- `auth.user.avatar` is always `string|null`.
- Profile page receives `avatar: UploaderExistingFile|null` for the existing file preview and `avatarUpload` route values generated with Wayfinder.
- Profile form submits `temporary_avatar_upload_id` from FilePond's staged ID.

- [ ] **Step 1: Extend the feature tests with shared-prop and Profile-page assertions.**

  Add an Inertia assertion after media exists:

  ```php
  $this->actingAs($user)
      ->get(route('profile.edit'))
      ->assertInertia(fn (Assert $page) => $page
          ->where('auth.user.avatar', $user->refresh()->avatarUrl())
          ->where('avatar.id', $user->getFirstMedia('avatar')->id)
      );
  ```

  After final-avatar deletion, assert `auth.user.avatar` is `null`. Keep the existing `ProfileUpdateTest` auth-user assertions intact and update them only to assert the explicit nullable `avatar` contract.

- [ ] **Step 2: Run the Profile and avatar tests to confirm the new assertions fail.**

  Run: `php artisan test --compact tests/Feature/Settings/AvatarUploadTest.php tests/Feature/Settings/ProfileUpdateTest.php`

  Expected: FAIL because shared props and Profile props do not yet expose avatar data.

- [ ] **Step 3: Make the backend props explicit.**

  In `HandleInertiaRequests::share()`, replace the raw authenticated user model with a helper that returns the normal serialized user attributes plus the explicit public conversion URL:

  ```php
  private function authenticatedUser(?User $user): ?array
  {
      if ($user === null) {
          return null;
      }

      return [
          ...$user->toArray(),
          'avatar' => $user->avatarUrl(),
      ];
  }
  ```

  Use `'user' => $this->authenticatedUser($request->user())` in the shared prop and import `App\Models\User`.

  In `ProfileController::edit()`, add an `avatar` prop built from `getFirstMedia('avatar')`, or `null` when absent:

  ```php
  'avatar' => $avatar === null ? null : [
      'id' => $avatar->id,
      'source' => $avatar->getUrl('avatar'),
      'poster' => $avatar->getUrl('avatar'),
      'name' => $avatar->name,
      'size' => $avatar->size,
      'type' => $avatar->mime_type,
  ],
  ```

- [ ] **Step 4: Wire Profile to the generic uploader with Wayfinder.**

  Run `php artisan wayfinder:generate --no-interaction` after route changes, then import the generated avatar upload and destroy route functions into `Profile.vue`.

  In the page script, keep a `ref<string[]>([])` for staged IDs and derive `temporaryAvatarUploadId` as its first entry. Configure `Uploader` with:

  ```vue
  <Uploader
      v-model="temporaryAvatarUploadIds"
      :upload-url="avatarUploads.store().url"
      :delete-url-resolver="(id) => avatarUploads.destroy(id).url"
      :multiple="false"
      :accepted-file-types="['image/jpeg', 'image/webp']"
      :max-file-size="2 * 1024 * 1024"
      upload-field-name="file"
      label-idle="Drop a JPEG or WebP avatar here, or browse"
  />
  <input type="hidden" name="temporary_avatar_upload_id" :value="temporaryAvatarUploadId ?? ''" />
  ```

  Render the current `avatar` prop above it using the existing Avatar primitives. Render a separate Inertia `<Link>` or button using `profile.avatar.destroy()` with `method="delete"` only when an avatar exists. Do not pass the final avatar as an `existingFiles` item to FilePond: final deletion must use its dedicated endpoint, while FilePond remains responsible only for staging. Preserve the current name/email/phone fields and single root element.

  Change `User` in `resources/js/types/auth.ts` to `avatar: string | null`; do not leave it optional. Existing `UserInfo.vue` and `AppHeader.vue` already use the image-or-initials behavior and should require no structural change once the prop is reliable.

- [ ] **Step 5: Run frontend checks and affected backend tests.**

  Run:

  ```bash
  pnpm exec prettier --write resources/js/pages/settings/Profile.vue resources/js/types/auth.ts resources/js/components/uploader/Uploader.vue
  pnpm exec eslint resources/js/pages/settings/Profile.vue resources/js/types/auth.ts resources/js/components/uploader/Uploader.vue
  pnpm exec vue-tsc --noEmit
  php artisan test --compact tests/Feature/Settings/AvatarUploadTest.php tests/Feature/Settings/ProfileUpdateTest.php
  ```

  Expected: all commands pass; the shell uses the same `auth.user.avatar` prop and falls back to initials when it is null.

- [ ] **Step 6: Commit the Profile and shared-prop integration.**

  ```bash
  git add app/Http/Middleware/HandleInertiaRequests.php app/Http/Controllers/Settings/ProfileController.php resources/js/types/auth.ts resources/js/pages/settings/Profile.vue resources/js/components/uploader/Uploader.vue resources/js/actions resources/js/routes tests/Feature/Settings/AvatarUploadTest.php tests/Feature/Settings/ProfileUpdateTest.php
  git commit -m "feat: manage avatars from profile settings"
  ```

### Task 4: Clean up abandoned staged uploads on a schedule

**Files:**
- Create: `app/Console/Commands/PurgeExpiredTemporaryAvatarUploads.php`
- Modify: `routes/console.php`
- Modify: `tests/Feature/Settings/AvatarUploadTest.php`

**Interfaces:**
- Produces `media:purge-expired-avatar-uploads`.
- The command deletes each `TemporaryAvatarUpload` with `expires_at <= now()`, removes its stored object when present, and returns success even when its object was previously removed.
- Scheduler invokes the command daily.

- [ ] **Step 1: Add failing cleanup tests.**

  Add these cases to `AvatarUploadTest.php`:

  ```php
  test('the cleanup command removes expired staged avatar uploads and their files', function () {
      Storage::fake('public');
      $upload = TemporaryAvatarUpload::factory()->expired()->create(['disk' => 'public']);
      Storage::disk('public')->put($upload->path, 'expired avatar');

      $this->artisan('media:purge-expired-avatar-uploads')
          ->assertSuccessful();

      expect(TemporaryAvatarUpload::find($upload->id))->toBeNull();
      Storage::disk('public')->assertMissing($upload->path);
  });

  test('the cleanup command keeps unexpired staged avatar uploads', function () {
      $upload = TemporaryAvatarUpload::factory()->create(['expires_at' => now()->addHour()]);

      $this->artisan('media:purge-expired-avatar-uploads')->assertSuccessful();

      expect(TemporaryAvatarUpload::find($upload->id))->not->toBeNull();
  });
  ```

  Generate a factory with Artisan and provide an `expired()` state that sets `expires_at` to `now()->subSecond()`.

- [ ] **Step 2: Run the cleanup tests and confirm failure.**

  Run: `php artisan test --compact tests/Feature/Settings/AvatarUploadTest.php --filter=cleanup`

  Expected: FAIL because the command and factory state do not exist.

- [ ] **Step 3: Implement the idempotent purge command and schedule.**

  Generate the command with Artisan. Its `handle()` should use `TemporaryAvatarUpload::query()->where('expires_at', '<=', now())->cursor()` and, for every row, execute:

  ```php
  Storage::disk($upload->disk)->delete($upload->path);
  $upload->delete();
  ```

  Keep the command non-interactive, return `self::SUCCESS`, and output a concise count. The disk is selected from the record so an upload staged before a configuration change is still deleted from its original disk.

  In `routes/console.php`, import `Illuminate\Support\Facades\Schedule` and schedule:

  ```php
  Schedule::command('media:purge-expired-avatar-uploads')->daily();
  ```

- [ ] **Step 4: Format and verify the command.**

  Run:

  ```bash
  vendor/bin/pint --dirty --format agent
  php artisan test --compact tests/Feature/Settings/AvatarUploadTest.php --filter=cleanup
  php artisan schedule:list
  ```

  Expected: cleanup tests PASS and the schedule list contains `media:purge-expired-avatar-uploads` with a daily cadence.

- [ ] **Step 5: Commit the cleanup task.**

  ```bash
  git add app/Console/Commands/PurgeExpiredTemporaryAvatarUploads.php database/factories/TemporaryAvatarUploadFactory.php routes/console.php tests/Feature/Settings/AvatarUploadTest.php
  git commit -m "feat: purge expired avatar uploads"
  ```

### Task 5: Final integration verification

**Files:**
- Modify only files required to correct failures discovered below.

**Interfaces:**
- Verifies all constraints: package migration runs, validated staged upload promotes to a single synchronous public avatar, R2 fallback remains deterministic, cleanup is scheduled, and the typed Vue UI builds.

- [ ] **Step 1: Run the complete focused backend suite.**

  Run:

  ```bash
  php artisan test --compact tests/Unit/Support/MediaDiskTest.php tests/Unit/Models/UserTest.php tests/Feature/Settings/AvatarUploadTest.php tests/Feature/Settings/ProfileUpdateTest.php
  ```

  Expected: PASS with coverage for disk selection, validation, authorization, promotion, replacement, deletion, shared props, and expiry cleanup.

- [ ] **Step 2: Run static and frontend verification.**

  Run:

  ```bash
  vendor/bin/pint --dirty --format agent
  pnpm exec eslint resources/js/pages/settings/Profile.vue resources/js/components/uploader/Uploader.vue resources/js/types/auth.ts
  pnpm exec vue-tsc --noEmit
  pnpm run build
  ```

  Expected: every command exits `0`. If the Vite manifest is missing before build, this command also regenerates it.

- [ ] **Step 3: Inspect generated routes and final changed-file scope.**

  Run:

  ```bash
  php artisan route:list --name=profile.avatar
  git diff --check
  git status --short
  ```

  Expected: the three authenticated avatar routes are present; no whitespace errors exist; any pre-existing unrelated working-tree changes remain untouched.

- [ ] **Step 4: Commit only final corrective changes, if any.**

  If a verification fix is needed, stage the exact corrected files reported by `git status --short` and commit them with `git commit -m "fix: verify avatar media integration"`. Skip this commit when final verification required no corrective edits.

## Plan self-review

| Spec requirement | Implementing task |
| --- | --- |
| R2 selection with public local fallback, no runtime fallback | Task 1 |
| User single-file avatar, JPEG/WebP 2 MiB, 256×256 WebP conversion | Task 1 |
| Authenticated FilePond staging and ownership | Task 2 |
| Save promotion, replacement, and explicit removal | Task 2 |
| Shared shell URL and Profile UI via Wayfinder | Task 3 |
| 24-hour idempotent cleanup and daily scheduling | Task 4 |
| Backend, static, formatting, and build checks | Task 5 |

No placeholders remain; interfaces are named consistently as `TemporaryAvatarUpload`, `PromoteTemporaryAvatarUpload`, `MediaDisk::avatar()`, and `temporary_avatar_upload_id`.
