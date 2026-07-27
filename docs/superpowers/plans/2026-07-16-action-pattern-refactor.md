# Action Pattern Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move six audited application operations from controllers and Artisan commands into independently tested, single-purpose Action classes without changing observable behavior.

**Architecture:** Add one final `handle()` Action per use-case under domain-specific `App\Actions` namespaces. HTTP controllers and Artisan commands remain transport adapters; typed result objects carry application outcomes back to those adapters without introducing HTTP or console dependencies into Actions.

**Tech Stack:** PHP 8.4, Laravel 12, Pest 4, Eloquent, Spatie Laravel Permission, Spatie Media Library.

## Global Constraints

- Preserve existing routes, request validation, command signatures, exit codes, console messages, HTTP responses, toast messages, database effects, and filesystem effects.
- Do not add dependencies, migrations, frontend changes, shared base classes, or generic service layers.
- New Actions are `final class` types with a public `handle()` method.
- Use constructor injection; do not call `app()` or `resolve()` inside Actions or commands.
- Keep HTTP, Inertia, and console presentation dependencies out of Actions.
- Follow red-green-refactor and run the smallest relevant Pest file after every implementation step.
- Do not run Wayfinder generation because no route, controller signature, route name, or route parameter changes.

---

### Task 1: Synchronize Authorization Action

**Files:**
- Create: `app/Actions/Authorization/AuthorizationSyncResult.php`
- Create: `app/Actions/Authorization/SyncAuthorization.php`
- Create: `tests/Feature/Actions/Authorization/SyncAuthorizationTest.php`
- Modify: `app/Console/Commands/SyncAuthorization.php`
- Verify: `tests/Feature/Console/SyncAuthorizationTest.php`

**Interfaces:**
- Consumes: `AuthorizationCatalog` and `PermissionRegistrar` through constructor injection.
- Produces: `SyncAuthorization::handle(bool $dryRun = false): AuthorizationSyncResult`.
- `AuthorizationSyncResult` exposes public readonly arrays `rolesToCreate`, `permissionsToCreate`, `rolesToDelete`, `permissionsToDelete`, `permissionsToAttachByRole`, and `permissionsToDetachByRole`, plus public readonly bool `dryRun`.

- [ ] **Step 1: Write failing Action tests**

Create the test with explicit apply and dry-run coverage:

```php
<?php

use App\Actions\Authorization\SyncAuthorization;
use App\Authorization\AuthorizationCatalog;
use App\Enums\Permission as PermissionEnum;
use App\Enums\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as PermissionRole;
use Spatie\Permission\PermissionRegistrar;

test('it reports and applies the authorization catalog diff', function () {
    PermissionRole::findOrCreate('obsolete-role');
    Permission::findOrCreate('obsolete.permission');

    $result = app(SyncAuthorization::class)->handle();

    expect($result->dryRun)->toBeFalse()
        ->and($result->rolesToDelete)->toContain('obsolete-role')
        ->and($result->permissionsToDelete)->toContain('obsolete.permission')
        ->and(PermissionRole::findByName(Role::SuperAdmin->value)
            ->hasPermissionTo(PermissionEnum::ManageSettings->value))->toBeTrue()
        ->and(PermissionRole::query()->where('name', 'obsolete-role')->exists())->toBeFalse();
});

test('dry run reports the diff without mutating records or permission cache', function () {
    PermissionRole::findOrCreate('obsolete-role');
    $registrar = mock(PermissionRegistrar::class);
    $registrar->shouldNotReceive('forgetCachedPermissions');
    app()->instance(PermissionRegistrar::class, $registrar);

    $action = new SyncAuthorization(app(AuthorizationCatalog::class), $registrar);
    $result = $action->handle(dryRun: true);

    expect($result->dryRun)->toBeTrue()
        ->and($result->rolesToDelete)->toContain('obsolete-role')
        ->and(PermissionRole::query()->where('name', 'obsolete-role')->exists())->toBeTrue();
});
```

- [ ] **Step 2: Run the tests and confirm the expected red state**

Run: `php artisan test --compact tests/Feature/Actions/Authorization/SyncAuthorizationTest.php`

Expected: FAIL because `App\Actions\Authorization\SyncAuthorization` does not exist.

- [ ] **Step 3: Add the typed result and minimal Action implementation**

Implement the result constructor with the exact public readonly fields from the interface block. Move `permissionNames()` and all catalog diff/query/mutation logic from the command into the Action. Build attach/detach maps for every declared role in both modes. Return the same result shape from dry-run and apply modes. In apply mode, call the injected registrar only after all mutations succeed:

```php
public function handle(bool $dryRun = false): AuthorizationSyncResult
{
    $result = $this->calculateResult($dryRun);

    if ($dryRun) {
        return $result;
    }

    $this->applyCatalog();
    $this->permissionRegistrar->forgetCachedPermissions();

    return $result;
}
```

- [ ] **Step 4: Run the Action tests until green**

Run: `php artisan test --compact tests/Feature/Actions/Authorization/SyncAuthorizationTest.php`

Expected: PASS with 2 tests and no failures.

- [ ] **Step 5: Refactor the Artisan command into a presentation adapter**

Inject `SyncAuthorization $syncAuthorization` into `handle()`. Pass the `--dry-run` boolean, render the returned arrays using the existing strings, and keep `self::SUCCESS`. Remove Eloquent, enum, catalog, and service-locator logic from the command.

```php
public function handle(SyncAuthorization $syncAuthorization): int
{
    $result = $syncAuthorization->handle((bool) $this->option('dry-run'));

    if ($result->dryRun) {
        $this->renderDryRun($result);
    } else {
        $this->components->info('Authorization catalog synchronized.');
    }

    return self::SUCCESS;
}
```

- [ ] **Step 6: Run Action and command regression tests**

Run: `php artisan test --compact tests/Feature/Actions/Authorization/SyncAuthorizationTest.php tests/Feature/Console/SyncAuthorizationTest.php`

Expected: PASS with all tests in both files.

- [ ] **Step 7: Commit the authorization slice**

```bash
git add app/Actions/Authorization app/Console/Commands/SyncAuthorization.php tests/Feature/Actions/Authorization/SyncAuthorizationTest.php
git commit -m "refactor: extract authorization sync action"
```

---

### Task 2: Initialize Super Admin Action

**Files:**
- Create: `app/Actions/Authorization/InitializeSuperAdmin.php`
- Create: `tests/Feature/Actions/Authorization/InitializeSuperAdminTest.php`
- Modify: `app/Console/Commands/InitializeSuperAdmin.php`
- Verify: `tests/Feature/Console/InitializeSuperAdminTest.php`

**Interfaces:**
- Consumes: validated array shape `array{name: string, email: string, phone: ?string, status: UserStatus, email_verified: bool, password: ?string}` and a reset flag.
- Produces: `InitializeSuperAdmin::handle(array $attributes, bool $resetPassword): User`.

- [ ] **Step 1: Write failing Action tests**

Cover create, idempotency, restoration, password preservation/reset, profile reconciliation, email verification, system flag, and enforced role. Start with these representative cases and use a local `superAdminAttributes()` helper for the exact array shape:

```php
test('it creates and restores the protected super admin', function () {
    $action = app(InitializeSuperAdmin::class);
    $user = $action->handle(superAdminAttributes(), false);

    expect($user->is_system)->toBeTrue()
        ->and($user->hasRole(Role::SuperAdmin))->toBeTrue();

    $user->deleteQuietly();
    $restored = $action->handle(superAdminAttributes(), false);

    expect($restored->id)->toBe($user->id)
        ->and($restored->trashed())->toBeFalse();
});

test('it only resets an existing password when requested', function () {
    $action = app(InitializeSuperAdmin::class);
    $user = $action->handle(superAdminAttributes(password: 'initial-password'), false);

    $user->update(['password' => 'preserved-password']);
    $action->handle(superAdminAttributes(password: 'replacement-password'), false);
    expect(Hash::check('preserved-password', $user->fresh()->password))->toBeTrue();

    $action->handle(superAdminAttributes(password: 'replacement-password'), true);
    expect(Hash::check('replacement-password', $user->fresh()->password))->toBeTrue();
});
```

- [ ] **Step 2: Verify the red state**

Run: `php artisan test --compact tests/Feature/Actions/Authorization/InitializeSuperAdminTest.php`

Expected: FAIL because the Action does not exist.

- [ ] **Step 3: Implement the Action**

Move user lookup, restoration, assignment, email verification, conditional password assignment, `saveQuietly()`, role creation, and `enforceSuperAdminRole()` into the Action. Keep configuration and environment access out of it.

```php
public function handle(array $attributes, bool $resetPassword): User
{
    $user = User::withTrashed()->where('is_system', true)->first()
        ?? User::withTrashed()->where('email', $attributes['email'])->first()
        ?? new User;

    $isNewUser = ! $user->exists;

    if ($user->trashed()) {
        $user->restore();
    }

    $user->name = $attributes['name'];
    $user->email = $attributes['email'];
    $user->phone = $attributes['phone'];
    $user->status = $attributes['status'];
    $user->is_system = true;

    if ($attributes['email_verified']) {
        $user->email_verified_at ??= Date::now();
    } else {
        $user->email_verified_at = null;
    }

    if ($isNewUser || $resetPassword) {
        $user->password = $attributes['password'];
    }

    $user->saveQuietly();
    PermissionRole::findOrCreate(Role::SuperAdmin->value);
    $user->enforceSuperAdminRole();

    return $user;
}
```

- [ ] **Step 4: Run the Action tests until green**

Run: `php artisan test --compact tests/Feature/Actions/Authorization/InitializeSuperAdminTest.php`

Expected: PASS with all Action cases.

- [ ] **Step 5: Make the command validate configuration and delegate**

Keep the existing missing-config and production-password failures. Normalize status to `UserStatus` in the command, call the Action, and render the returned email.

```php
$user = $initializeSuperAdmin->handle([
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'status' => $status instanceof UserStatus ? $status : UserStatus::from($status),
    'email_verified' => (bool) $emailVerified,
    'password' => is_string($password) ? $password : null,
], (bool) $this->option('reset-password'));
```

- [ ] **Step 6: Run Action and command regression tests**

Run: `php artisan test --compact tests/Feature/Actions/Authorization/InitializeSuperAdminTest.php tests/Feature/Console/InitializeSuperAdminTest.php`

Expected: PASS with all tests in both files.

- [ ] **Step 7: Commit the Super Admin slice**

```bash
git add app/Actions/Authorization/InitializeSuperAdmin.php app/Console/Commands/InitializeSuperAdmin.php tests/Feature/Actions/Authorization/InitializeSuperAdminTest.php
git commit -m "refactor: extract super admin initialization action"
```

---

### Task 3: Stage Temporary Avatar Action

**Files:**
- Create: `app/Actions/Profile/StageTemporaryAvatarUpload.php`
- Create: `tests/Feature/Actions/Profile/StageTemporaryAvatarUploadTest.php`
- Modify: `app/Http/Controllers/Profile/AvatarUploadController.php`
- Verify: `tests/Feature/Profile/AvatarUploadTest.php`

**Interfaces:**
- Consumes: `User` and validated `UploadedFile`.
- Produces: `StageTemporaryAvatarUpload::handle(User $user, UploadedFile $file): TemporaryAvatarUpload`.

- [ ] **Step 1: Write failing Action tests for persistence and compensation**

```php
beforeEach(function () {
    $this->freezeTime();
    Storage::fake('public');
});

test('it stores a temporary avatar and records its metadata', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg');

    $upload = app(StageTemporaryAvatarUpload::class)->handle($user, $file);

    expect($upload->user_id)->toBe($user->id)
        ->and($upload->disk)->toBe(MediaDisk::avatar())
        ->and($upload->original_name)->toBe('avatar.jpg')
        ->and($upload->expires_at->equalTo(now()->addDay()))->toBeTrue();
    Storage::disk($upload->disk)->assertExists($upload->path);
});

test('it removes the stored file when record creation fails', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg');
    TemporaryAvatarUpload::creating(fn () => throw new RuntimeException('database unavailable'));

    expect(fn () => app(StageTemporaryAvatarUpload::class)->handle($user, $file))
        ->toThrow(RuntimeException::class, 'database unavailable');

    expect(Storage::disk(MediaDisk::avatar())->allFiles("temporary-avatars/{$user->id}"))->toBeEmpty();
});
```

- [ ] **Step 2: Verify the red state**

Run: `php artisan test --compact tests/Feature/Actions/Profile/StageTemporaryAvatarUploadTest.php`

Expected: FAIL because the Action does not exist.

- [ ] **Step 3: Implement storage, persistence, and compensating cleanup**

Use `MediaDisk::avatar()`, the current directory convention, current metadata fields, and `now()->addDay()`. Wrap only record creation so a failed insert deletes the already stored path and then rethrows the original `Throwable`.

```php
$path = $file->store("temporary-avatars/{$user->id}", ['disk' => $disk]);

try {
    return TemporaryAvatarUpload::query()->create([
        'user_id' => $user->id,
        'disk' => $disk,
        'path' => $path,
        'original_name' => $file->getClientOriginalName(),
        'mime_type' => $file->getClientMimeType(),
        'size' => $file->getSize(),
        'expires_at' => now()->addDay(),
    ]);
} catch (Throwable $throwable) {
    Storage::disk($disk)->delete($path);
    throw $throwable;
}
```

- [ ] **Step 4: Run the Action tests until green**

Run: `php artisan test --compact tests/Feature/Actions/Profile/StageTemporaryAvatarUploadTest.php`

Expected: PASS with 2 tests.

- [ ] **Step 5: Delegate from `AvatarUploadController::store()`**

Inject the Action into `store()`, pass the authenticated user and validated file, and leave the existing 201 JSON mapping unchanged.

- [ ] **Step 6: Run Action and profile regression tests**

Run: `php artisan test --compact tests/Feature/Actions/Profile/StageTemporaryAvatarUploadTest.php tests/Feature/Profile/AvatarUploadTest.php`

Expected: PASS with all tests in both files.

- [ ] **Step 7: Commit the staging slice**

```bash
git add app/Actions/Profile/StageTemporaryAvatarUpload.php app/Http/Controllers/Profile/AvatarUploadController.php tests/Feature/Actions/Profile/StageTemporaryAvatarUploadTest.php
git commit -m "refactor: extract avatar staging action"
```

---

### Task 4: Delete Temporary Avatar Action

**Files:**
- Create: `app/Actions/Profile/TemporaryAvatarDeletionResult.php`
- Create: `app/Actions/Profile/DeleteTemporaryAvatarUpload.php`
- Create: `tests/Feature/Actions/Profile/DeleteTemporaryAvatarUploadTest.php`
- Modify: `app/Http/Controllers/Profile/AvatarUploadController.php`
- Verify: `tests/Feature/Profile/AvatarUploadTest.php`

**Interfaces:**
- Consumes: authenticated `User` and upload UUID string.
- Produces: `DeleteTemporaryAvatarUpload::handle(User $user, string $uploadId): TemporaryAvatarDeletionResult`.
- Enum cases: `Deleted`, `Missing`, `Forbidden`.

- [ ] **Step 1: Write failing tests for all three outcomes**

```php
test('it reports deleted, missing, and forbidden outcomes', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownedUpload = TemporaryAvatarUpload::factory()->for($owner)->create();
    $foreignUpload = TemporaryAvatarUpload::factory()->for($otherUser)->create();
    $action = app(DeleteTemporaryAvatarUpload::class);

    expect($action->handle($owner, $ownedUpload->id))->toBe(TemporaryAvatarDeletionResult::Deleted)
        ->and($ownedUpload->fresh())->toBeNull()
        ->and($action->handle($owner, $ownedUpload->id))->toBe(TemporaryAvatarDeletionResult::Missing)
        ->and($action->handle($owner, $foreignUpload->id))->toBe(TemporaryAvatarDeletionResult::Forbidden)
        ->and($foreignUpload->fresh())->not->toBeNull();
});
```

- [ ] **Step 2: Verify the red state**

Run: `php artisan test --compact tests/Feature/Actions/Profile/DeleteTemporaryAvatarUploadTest.php`

Expected: FAIL because the Action and enum do not exist.

- [ ] **Step 3: Implement the result enum and Action**

Query by ID first to preserve the distinction between missing and foreign ownership. Compare UUID strings strictly, delete only an owned upload, and return the corresponding enum case.

- [ ] **Step 4: Run the Action tests until green**

Run: `php artisan test --compact tests/Feature/Actions/Profile/DeleteTemporaryAvatarUploadTest.php`

Expected: PASS.

- [ ] **Step 5: Translate the enum in `AvatarUploadController::destroy()`**

```php
$result = $deleteTemporaryAvatarUpload->handle($request->user(), $temporaryAvatarUpload);

abort_if($result === TemporaryAvatarDeletionResult::Forbidden, 404);

return response()->noContent();
```

Both `Deleted` and `Missing` intentionally produce 204.

- [ ] **Step 6: Run Action and controller regression tests**

Run: `php artisan test --compact tests/Feature/Actions/Profile/DeleteTemporaryAvatarUploadTest.php tests/Feature/Profile/AvatarUploadTest.php`

Expected: PASS, including repeated deletion and foreign-user 404 coverage.

- [ ] **Step 7: Commit the temporary deletion slice**

```bash
git add app/Actions/Profile app/Http/Controllers/Profile/AvatarUploadController.php tests/Feature/Actions/Profile/DeleteTemporaryAvatarUploadTest.php
git commit -m "refactor: extract temporary avatar deletion action"
```

---

### Task 5: Remove User Avatar Action

**Files:**
- Create: `app/Actions/Profile/RemoveUserAvatar.php`
- Create: `tests/Feature/Actions/Profile/RemoveUserAvatarTest.php`
- Modify: `app/Http/Controllers/Profile/ProfileController.php`
- Verify: `tests/Feature/Profile/AvatarUploadTest.php`

**Interfaces:**
- Consumes: authenticated `User`.
- Produces: `RemoveUserAvatar::handle(User $user): void`.

- [ ] **Step 1: Write the failing Action test**

```php
test('it removes the users avatar media', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))->toMediaCollection('avatar');

    app(RemoveUserAvatar::class)->handle($user);

    expect($user->refresh()->hasMedia('avatar'))->toBeFalse();
});
```

- [ ] **Step 2: Verify the red state**

Run: `php artisan test --compact tests/Feature/Actions/Profile/RemoveUserAvatarTest.php`

Expected: FAIL because the Action does not exist.

- [ ] **Step 3: Implement the Action**

```php
public function handle(User $user): void
{
    $user->clearMediaCollection('avatar');
}
```

- [ ] **Step 4: Run the Action test until green**

Run: `php artisan test --compact tests/Feature/Actions/Profile/RemoveUserAvatarTest.php`

Expected: PASS.

- [ ] **Step 5: Delegate from `ProfileController::destroyAvatar()`**

Inject `RemoveUserAvatar`, call it with `$request->user()`, and preserve the existing toast and redirect exactly.

- [ ] **Step 6: Run Action and profile regression tests**

Run: `php artisan test --compact tests/Feature/Actions/Profile/RemoveUserAvatarTest.php tests/Feature/Profile/AvatarUploadTest.php tests/Feature/Profile/ProfileUpdateTest.php`

Expected: PASS with all tests in all three files.

- [ ] **Step 7: Commit the avatar removal slice**

```bash
git add app/Actions/Profile/RemoveUserAvatar.php app/Http/Controllers/Profile/ProfileController.php tests/Feature/Actions/Profile/RemoveUserAvatarTest.php
git commit -m "refactor: extract avatar removal action"
```

---

### Task 6: Purge Expired Avatar Uploads Action

**Files:**
- Create: `app/Actions/Profile/PurgeExpiredAvatarUploads.php`
- Create: `tests/Feature/Actions/Profile/PurgeExpiredAvatarUploadsTest.php`
- Modify: `app/Console/Commands/PurgeExpiredAvatarUploads.php`
- Verify: `tests/Feature/PurgeExpiredAvatarUploadsCommandTest.php`

**Interfaces:**
- Consumes: no arguments; uses the model query and current clock.
- Produces: `PurgeExpiredAvatarUploads::handle(): int` containing the number deleted.

- [ ] **Step 1: Write failing Action tests**

```php
test('it deletes only expired uploads and returns the deleted count', function () {
    $this->freezeTime();
    Storage::fake('public');
    $expired = TemporaryAvatarUpload::factory()->expired()->create();
    $expiresNow = TemporaryAvatarUpload::factory()->create(['expires_at' => now()]);
    $unexpired = TemporaryAvatarUpload::factory()->create(['expires_at' => now()->addSecond()]);

    foreach ([$expired, $expiresNow, $unexpired] as $upload) {
        Storage::disk($upload->disk)->put($upload->path, 'contents');
    }

    $count = app(PurgeExpiredAvatarUploads::class)->handle();

    expect($count)->toBe(2)
        ->and($expired->fresh())->toBeNull()
        ->and($expiresNow->fresh())->toBeNull()
        ->and($unexpired->fresh())->not->toBeNull();
});
```

Retain the existing command-level test for an undeletable stored file; the exception must still stop deletion and leave the record intact.

- [ ] **Step 2: Verify the red state**

Run: `php artisan test --compact tests/Feature/Actions/Profile/PurgeExpiredAvatarUploadsTest.php`

Expected: FAIL because the Action does not exist.

- [ ] **Step 3: Implement cursor-based deletion and counting**

```php
public function handle(): int
{
    $deleted = 0;

    TemporaryAvatarUpload::query()
        ->where('expires_at', '<=', now())
        ->cursor()
        ->each(function (TemporaryAvatarUpload $upload) use (&$deleted): void {
            $upload->delete();
            $deleted++;
        });

    return $deleted;
}
```

- [ ] **Step 4: Run the Action tests until green**

Run: `php artisan test --compact tests/Feature/Actions/Profile/PurgeExpiredAvatarUploadsTest.php`

Expected: PASS.

- [ ] **Step 5: Make the command a thin adapter**

Inject the Action into `handle()`, invoke it, ignore the count for now to preserve output exactly, and return `self::SUCCESS`.

- [ ] **Step 6: Run Action, command, and scheduling regression tests**

Run: `php artisan test --compact tests/Feature/Actions/Profile/PurgeExpiredAvatarUploadsTest.php tests/Feature/PurgeExpiredAvatarUploadsCommandTest.php`

Expected: PASS, including missing-file idempotency, undeletable-file failure, and schedule coverage.

- [ ] **Step 7: Commit the purge slice**

```bash
git add app/Actions/Profile/PurgeExpiredAvatarUploads.php app/Console/Commands/PurgeExpiredAvatarUploads.php tests/Feature/Actions/Profile/PurgeExpiredAvatarUploadsTest.php
git commit -m "refactor: extract expired avatar purge action"
```

---

### Task 7: Architecture Guard and Final Verification

**Files:**
- Create: `tests/Unit/Architecture/ActionArchitectureTest.php`
- Verify: all files changed since spec commit `304fb233f6d58378e49136e93c508b1f0c03b2d4`

**Interfaces:**
- Consumes: final Action namespace structure.
- Produces: an architecture regression test preventing framework presentation dependencies from entering Action classes.

- [ ] **Step 1: Add the architecture test**

```php
<?php

arch('new domain actions are final application classes')
    ->expect([
        'App\Actions\Authorization',
        'App\Actions\Profile',
    ])
    ->toBeFinal();

arch('actions do not depend on transport presentation types')
    ->expect([
        'App\Actions\Authorization',
        'App\Actions\Profile',
    ])
    ->not->toUse([
        'Illuminate\Console\Command',
        'Illuminate\Http\Request',
        'Inertia\Inertia',
    ]);
```

- [ ] **Step 2: Run the architecture test**

Run: `php artisan test --compact tests/Unit/Architecture/ActionArchitectureTest.php`

Expected: PASS. The checks intentionally exclude existing Fortify Actions,
which are not final and are outside this refactor's scope.

- [ ] **Step 3: Run the complete targeted backend suite**

Run:

```bash
php artisan test --compact \
  tests/Feature/Actions \
  tests/Feature/Console/SyncAuthorizationTest.php \
  tests/Feature/Console/InitializeSuperAdminTest.php \
  tests/Feature/Profile/AvatarUploadTest.php \
  tests/Feature/Profile/ProfileUpdateTest.php \
  tests/Feature/PurgeExpiredAvatarUploadsCommandTest.php \
  tests/Unit/Architecture/ActionArchitectureTest.php
```

Expected: PASS with zero failures.

- [ ] **Step 4: Run safe automatic formatting**

Run:

```bash
vendor/bin/pint --dirty --format agent
pnpm run lint
pnpm run format
```

Expected: all commands exit 0. Review any formatter changes and ensure they are limited to owned files.

- [ ] **Step 5: Run the full finishing gate**

Run: `composer run agent:gate`

Expected: exit 0 from PHP lint, format checks, type checks, complete test suite, and frontend build.

- [ ] **Step 6: Review the final diff against the spec fixed point**

Run:

```bash
git status --short
git diff --check 304fb233f6d58378e49136e93c508b1f0c03b2d4..HEAD
git diff --stat 304fb233f6d58378e49136e93c508b1f0c03b2d4..HEAD
git diff 304fb233f6d58378e49136e93c508b1f0c03b2d4..HEAD -- app tests
```

Expected: only the six approved Action slices, their adapters/result types/tests, and the architecture test are present. Confirm no route, migration, frontend, dependency, or generated Wayfinder files changed.

- [ ] **Step 7: Obtain independent review**

Give a read-only reviewer the fixed diff from `304fb233f6d58378e49136e93c508b1f0c03b2d4` through the final implementation commit. Require findings to include severity, file/symbol, violated requirement, evidence, impact, and recommended fix. Resolve accepted findings and repeat the smallest affected test plus `composer run agent:gate`.

- [ ] **Step 8: Commit the architecture guard and any verified review fixes**

```bash
git add tests/Unit/Architecture/ActionArchitectureTest.php
git commit -m "test: enforce action architecture boundaries"
```

Do not push or open a pull request unless explicitly requested.
