# Action Pattern Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Memindahkan business mutation logic Settings ke action terpisah dengan method `handle()` tanpa mengubah perilaku HTTP.

**Architecture:** Action aplikasi berada di `app/Actions`, satu class untuk satu use case, dan menerima model serta data tervalidasi tanpa dependency HTTP. Controller Settings tetap menjadi adapter HTTP yang mengatur response, flash message, redirect, logout, dan lifecycle session.

**Tech Stack:** PHP 8.5, Laravel 13, Pest 4, Eloquent, Inertia v3, Laravel Pint.

## Global Constraints

- Semua action baru menggunakan method publik `handle()`, bukan `__invoke()`.
- Action tidak menerima `Request` atau `FormRequest` dan tidak mengembalikan HTTP response.
- Jangan membuat `BaseAction`, `ActionContract`, service generik, atau dependency baru.
- Implementasi action berada di `app/Actions`.
- Unit test action berada di `tests/Unit/Auth/Actions` tanpa folder parent `Domain`.
- Feature test endpoint tetap berada di `tests/Feature/Settings`.
- Route names, middleware, payload, redirect, flash message, dan perilaku pengguna harus tetap kompatibel.
- Gunakan Pest dan factory `User` untuk setup test.
- Setelah mengubah PHP, jalankan `vendor/bin/pint --dirty --format agent`.

---

### Task 1: Extract UpdateUserProfile action

**Files:**
- Create: `app/Actions/UpdateUserProfile.php`
- Create: `tests/Unit/Auth/Actions/UpdateUserProfileTest.php`
- Modify: `app/Http/Controllers/Settings/ProfileController.php`
- Test: `tests/Feature/Settings/ProfileUpdateTest.php`

**Interfaces:**
- Consumes: `User $user` dan `array{name: string, email: string} $attributes`.
- Produces: `UpdateUserProfile::handle(User $user, array $attributes): User`.

- [ ] **Step 1: Create the Pest unit test file**

Run:

```bash
php artisan make:test --pest --unit Auth/Actions/UpdateUserProfileTest --no-interaction
```

If the generator places the file outside `tests/Unit/Auth/Actions`, move it into the required path using the repository file-editing workflow, then declare the test cases below.

- [ ] **Step 2: Write failing tests for the profile action**

Add these cases to `tests/Unit/Auth/Actions/UpdateUserProfileTest.php`:

```php
use App\Actions\UpdateUserProfile;
use App\Models\User;

test('it updates profile attributes', function () {
    $user = User::factory()->create();

    $updatedUser = (new UpdateUserProfile)->handle($user, [
        'name' => 'Updated User',
        'email' => 'updated@example.com',
    ]);

    expect($updatedUser->name)->toBe('Updated User')
        ->and($updatedUser->email)->toBe('updated@example.com');
});

test('it clears email verification when the email changes', function () {
    $user = User::factory()->create();

    (new UpdateUserProfile)->handle($user, [
        'name' => $user->name,
        'email' => 'changed@example.com',
    ]);

    expect($user->refresh()->email_verified_at)->toBeNull();
});

test('it preserves email verification when the email is unchanged', function () {
    $user = User::factory()->create();

    (new UpdateUserProfile)->handle($user, [
        'name' => 'Updated User',
        'email' => $user->email,
    ]);

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});
```

- [ ] **Step 3: Run the focused unit test and confirm it fails**

Run:

```bash
php artisan test --compact tests/Unit/Auth/Actions/UpdateUserProfileTest.php
```

Expected: FAIL because `App\Actions\UpdateUserProfile` does not exist.

- [ ] **Step 4: Implement the action**

Create `app/Actions/UpdateUserProfile.php` with this behavior:

```php
<?php

namespace App\Actions;

use App\Models\User;

final class UpdateUserProfile
{
    /**
     * @param array{name: string, email: string} $attributes
     */
    public function handle(User $user, array $attributes): User
    {
        $user->fill($attributes);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user;
    }
}
```

- [ ] **Step 5: Delegate from ProfileController**

Inject `UpdateUserProfile` into `ProfileController::update()`, call `handle($request->user(), $request->validated())`, and leave the existing Inertia flash and `profile.edit` redirect unchanged.

- [ ] **Step 6: Run unit and feature tests**

Run:

```bash
php artisan test --compact tests/Unit/Auth/Actions/UpdateUserProfileTest.php tests/Feature/Settings/ProfileUpdateTest.php
```

Expected: PASS, including both email verification scenarios.

- [ ] **Step 7: Format and commit**

Run:

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/UpdateUserProfile.php app/Http/Controllers/Settings/ProfileController.php tests/Unit/Auth/Actions/UpdateUserProfileTest.php tests/Feature/Settings/ProfileUpdateTest.php
git commit -m "refactor: extract profile update action"
```

### Task 2: Extract DeleteUserAccount action

**Files:**
- Create: `app/Actions/DeleteUserAccount.php`
- Create: `tests/Unit/Auth/Actions/DeleteUserAccountTest.php`
- Modify: `app/Http/Controllers/Settings/ProfileController.php`
- Test: `tests/Feature/Settings/ProfileUpdateTest.php`

**Interfaces:**
- Consumes: `User $user`.
- Produces: `DeleteUserAccount::handle(User $user): void`.

- [ ] **Step 1: Create the Pest unit test file**

Run:

```bash
php artisan make:test --pest --unit Auth/Actions/DeleteUserAccountTest --no-interaction
```

Ensure the resulting file is `tests/Unit/Auth/Actions/DeleteUserAccountTest.php`.

- [ ] **Step 2: Write the failing deletion test**

```php
use App\Actions\DeleteUserAccount;
use App\Models\User;

test('it deletes the user account', function () {
    $user = User::factory()->create();

    (new DeleteUserAccount)->handle($user);

    expect($user->fresh())->toBeNull();
});
```

- [ ] **Step 3: Run the focused test and confirm it fails**

Run:

```bash
php artisan test --compact tests/Unit/Auth/Actions/DeleteUserAccountTest.php
```

Expected: FAIL because `App\Actions\DeleteUserAccount` does not exist.

- [ ] **Step 4: Implement the action**

Create `app/Actions/DeleteUserAccount.php`:

```php
<?php

namespace App\Actions;

use App\Models\User;

final class DeleteUserAccount
{
    public function handle(User $user): void
    {
        $user->delete();
    }
}
```

- [ ] **Step 5: Delegate deletion from ProfileController**

Inject `DeleteUserAccount` into `ProfileController::destroy()`. Preserve the existing order and HTTP responsibilities: capture the user, logout through `Auth`, call `handle($user)`, invalidate the session, regenerate the token, and redirect to the existing home route.

- [ ] **Step 6: Run deletion tests**

Run:

```bash
php artisan test --compact tests/Unit/Auth/Actions/DeleteUserAccountTest.php tests/Feature/Settings/ProfileUpdateTest.php --filter="delete|account|password"
```

Expected: PASS for successful deletion, guest state, and wrong-password protection.

- [ ] **Step 7: Format and commit**

Run:

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/DeleteUserAccount.php app/Http/Controllers/Settings/ProfileController.php tests/Unit/Auth/Actions/DeleteUserAccountTest.php tests/Feature/Settings/ProfileUpdateTest.php
git commit -m "refactor: extract account deletion action"
```

### Task 3: Extract UpdateUserPassword action

**Files:**
- Create: `app/Actions/UpdateUserPassword.php`
- Create: `tests/Unit/Auth/Actions/UpdateUserPasswordTest.php`
- Modify: `app/Http/Controllers/Settings/SecurityController.php`
- Test: `tests/Feature/Settings/SecurityTest.php`

**Interfaces:**
- Consumes: `User $user` dan `string $password` yang sudah divalidasi oleh `PasswordUpdateRequest`.
- Produces: `UpdateUserPassword::handle(User $user, string $password): void`.

- [ ] **Step 1: Create the Pest unit test file**

Run:

```bash
php artisan make:test --pest --unit Auth/Actions/UpdateUserPasswordTest --no-interaction
```

Ensure the resulting file is `tests/Unit/Auth/Actions/UpdateUserPasswordTest.php`.

- [ ] **Step 2: Write the failing password test**

```php
use App\Actions\UpdateUserPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it updates the user password', function () {
    $user = User::factory()->create();

    (new UpdateUserPassword)->handle($user, 'new-password');

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});
```

- [ ] **Step 3: Run the focused test and confirm it fails**

Run:

```bash
php artisan test --compact tests/Unit/Auth/Actions/UpdateUserPasswordTest.php
```

Expected: FAIL because `App\Actions\UpdateUserPassword` does not exist.

- [ ] **Step 4: Implement the action**

Create `app/Actions/UpdateUserPassword.php`:

```php
<?php

namespace App\Actions;

use App\Models\User;

final class UpdateUserPassword
{
    public function handle(User $user, string $password): void
    {
        $user->update([
            'password' => $password,
        ]);
    }
}
```

The `User` model's `hashed` cast remains the single password-hashing mechanism; do not add manual hashing inside the action.

- [ ] **Step 5: Delegate from SecurityController**

Inject `UpdateUserPassword` into `SecurityController::update()`, call `handle($request->user(), $request->password)`, and preserve the existing success flash and `back()` response.

- [ ] **Step 6: Run password tests**

Run:

```bash
php artisan test --compact tests/Unit/Auth/Actions/UpdateUserPasswordTest.php tests/Feature/Settings/SecurityTest.php --filter="password"
```

Expected: PASS for password update and current-password validation failure.

- [ ] **Step 7: Format and commit**

Run:

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/UpdateUserPassword.php app/Http/Controllers/Settings/SecurityController.php tests/Unit/Auth/Actions/UpdateUserPasswordTest.php tests/Feature/Settings/SecurityTest.php
git commit -m "refactor: extract password update action"
```

### Task 4: Verify the refactor and enforce the structure

**Files:**
- Modify: `tests/Unit/Auth/Actions/UpdateUserProfileTest.php` only if formatting or test isolation requires it.
- Modify: `tests/Unit/Auth/Actions/DeleteUserAccountTest.php` only if formatting or test isolation requires it.
- Modify: `tests/Unit/Auth/Actions/UpdateUserPasswordTest.php` only if formatting or test isolation requires it.

**Interfaces:**
- Consumes: the three action contracts from Tasks 1–3.
- Produces: verified action boundaries and unchanged Settings behavior.

- [ ] **Step 1: Confirm no controller mutation logic remains**

Inspect the two Settings controllers and verify they only contain request extraction, action calls, Inertia rendering, flash messages, redirects, logout, and session operations. The controllers must not directly call `$user->fill()`, `$user->update()`, `$user->save()`, or `$user->delete()` for these use cases.

- [ ] **Step 2: Run all related tests**

Run:

```bash
php artisan test --compact tests/Unit/Auth/Actions tests/Feature/Settings
```

Expected: PASS with no failures.

- [ ] **Step 3: Run formatting and static quality checks**

Run:

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

Expected: Pint reports no remaining formatting changes and the complete Pest suite passes.

- [ ] **Step 4: Verify the final diff**

Run:

```bash
git diff --check HEAD~3..HEAD
git status --short
```

Expected: no whitespace errors; only the intended action, controller, and `tests/Unit/Auth/Actions` files are changed or added.

- [ ] **Step 5: Commit any final formatting correction**

If Step 3 changed PHP files, run:

```bash
git add app/Actions app/Http/Controllers/Settings tests/Unit/Auth/Actions
git commit -m "style: format action pattern refactor"
```

If Pint made no changes, do not create an empty commit.
