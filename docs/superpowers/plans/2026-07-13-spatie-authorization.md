# Spatie Authorization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Integrate Spatie Laravel Permission with UUID users, a strict code-owned authorization catalog, and a protected configurable Super Admin account.

**Architecture:** Backed enums and an authorization catalog own the role and permission declarations. Artisan commands apply the catalog and initialize the protected account. The `User` model integrates Spatie and enforces system-account invariants, while a Gate-before callback provides the Super Admin bypass.

**Tech Stack:** Laravel 13, PHP 8.5, Spatie Laravel Permission 8, Pest 4, SQLite test database.

## Global Constraints

- Do not change Composer or JavaScript dependencies.
- Preserve existing uncommitted package-install files; only make targeted edits needed for UUID compatibility.
- Use backed enum cases in TitleCase and scalar `resource.action` permission values for future permissions.
- All production secrets are read through configuration; only local/testing may fall back to `password`.
- Use `php artisan make:* --no-interaction` to scaffold application files.
- Write and observe a failing Pest test before each production behavior change.
- Run `vendor/bin/pint --dirty --format agent` after PHP edits.

---

### Task 1: Define the authorization catalog and Super Admin configuration

**Files:**

- Create: `app/Enums/Role.php`
- Create: `app/Enums/Permission.php`
- Create: `app/Authorization/AuthorizationCatalog.php`
- Create: `config/superadmin.php`
- Test: `tests/Unit/Authorization/AuthorizationCatalogTest.php`

**Interfaces:**

- Produces `Role::SuperAdmin` with value `super-admin`.
- Produces `AuthorizationCatalog::roles(): array`, `permissions(): array`, and `permissionsFor(Role $role): array`.
- Produces configuration keys `superadmin.name`, `email`, `phone`, `status`, `email_verified`, and `password`.

- [ ] **Step 1: Write the failing catalog and configuration test.**

```php
use App\Authorization\AuthorizationCatalog;
use App\Enums\Role;

test('the initial catalog declares only the super admin role', function () {
    $catalog = app(AuthorizationCatalog::class);

    expect($catalog->roles())->toBe([Role::SuperAdmin])
        ->and($catalog->permissions())->toBe([])
        ->and($catalog->permissionsFor(Role::SuperAdmin))->toBe([]);
});

test('the testing super admin password defaults to password', function () {
    expect(config('superadmin.password'))->toBe('password');
});
```

- [ ] **Step 2: Run the test to verify it fails.**

Run: `php artisan test --compact tests/Unit/Authorization/AuthorizationCatalogTest.php`

Expected: FAIL because the catalog, enums, and configuration do not exist.

- [ ] **Step 3: Implement the enum, catalog, and configuration contract.**

```php
enum Role: string
{
    case SuperAdmin = 'super-admin';
}

final class AuthorizationCatalog
{
    /** @return list<Role> */
    public function roles(): array
    {
        return [Role::SuperAdmin];
    }

    /** @return list<Permission> */
    public function permissions(): array
    {
        return [];
    }

    /** @return list<Permission> */
    public function permissionsFor(Role $role): array
    {
        return [];
    }
}
```

Define `config/superadmin.php` with environment-backed profile values. Use `app()->environment('local', 'testing') ? 'password' : null` as the password fallback; production validation occurs in the initializer.

- [ ] **Step 4: Run the focused test to verify it passes.**

Run: `php artisan test --compact tests/Unit/Authorization/AuthorizationCatalogTest.php`

Expected: PASS.

- [ ] **Step 5: Commit the completed task.**

```bash
git add app/Enums app/Authorization config/superadmin.php tests/Unit/Authorization/AuthorizationCatalogTest.php
git commit -m "feat: add authorization catalog"
```

### Task 2: Make Spatie's schema and User model UUID-compatible

**Files:**

- Modify: `database/migrations/2026_07_13_044317_create_permission_tables.php`
- Create: `database/migrations/<timestamp>_add_is_system_to_users_table.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Authorization/UserAuthorizationTest.php`

**Interfaces:**

- Produces `User::$is_system` as a boolean cast and a protected-system-account predicate.
- Produces UUID-compatible `model_has_roles.model_id` and `model_has_permissions.model_id` columns.
- `User` uses Spatie `HasRoles`.

- [ ] **Step 1: Write the failing User integration test.**

```php
use App\Enums\Role;
use App\Models\User;
use Spatie\Permission\Models\Role as PermissionRole;

test('a UUID user can receive the super admin role', function () {
    $user = User::factory()->create();
    PermissionRole::findOrCreate(Role::SuperAdmin->value);

    $user->assignRole(Role::SuperAdmin);

    expect($user->fresh()->hasRole(Role::SuperAdmin))->toBeTrue();
});
```

- [ ] **Step 2: Run the test to verify it fails.**

Run: `php artisan test --compact tests/Feature/Authorization/UserAuthorizationTest.php`

Expected: FAIL because `User` does not use `HasRoles` and the published pivot schema is not UUID-compatible.

- [ ] **Step 3: Apply the minimum schema and model changes.**

```php
// In each affected unpublished Spatie pivot-table definition:
$table->uuid($columnNames['model_morph_key']);
```

Generate a new migration with `php artisan make:migration add_is_system_to_users_table --no-interaction`; add a non-null boolean `is_system` with default `false` and a reversible down method. Add the attribute to `User`'s fillable list only if the existing application needs a trusted mass-assignment path, add the boolean cast, and add `HasRoles`.

- [ ] **Step 4: Run the focused test to verify it passes.**

Run: `php artisan test --compact tests/Feature/Authorization/UserAuthorizationTest.php`

Expected: PASS.

- [ ] **Step 5: Commit the completed task.**

```bash
git add app/Models/User.php database/migrations tests/Feature/Authorization/UserAuthorizationTest.php
git commit -m "feat: integrate UUID users with roles"
```

### Task 3: Add strict authorization synchronization

**Files:**

- Create: `app/Console/Commands/SyncAuthorization.php`
- Test: `tests/Feature/Console/SyncAuthorizationTest.php`

**Interfaces:**

- Produces `php artisan auth:sync-authorization`.
- Accepts `--dry-run`.
- Applies the `AuthorizationCatalog` as the complete source of truth.

- [ ] **Step 1: Write the failing command tests.**

```php
use App\Enums\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as PermissionRole;

test('the authorization sync creates declared roles and prunes undeclared entries', function () {
    PermissionRole::findOrCreate('obsolete-role');
    Permission::findOrCreate('obsolete.permission');

    $this->artisan('auth:sync-authorization')->assertSuccessful();

    expect(PermissionRole::findByName(Role::SuperAdmin->value))->not->toBeNull()
        ->and(PermissionRole::where('name', 'obsolete-role')->exists())->toBeFalse()
        ->and(Permission::where('name', 'obsolete.permission')->exists())->toBeFalse();
});

test('the authorization sync dry run does not mutate records', function () {
    PermissionRole::findOrCreate('obsolete-role');

    $this->artisan('auth:sync-authorization', ['--dry-run' => true])->assertSuccessful();

    expect(PermissionRole::where('name', 'obsolete-role')->exists())->toBeTrue();
});
```

- [ ] **Step 2: Run the command test to verify it fails.**

Run: `php artisan test --compact tests/Feature/Console/SyncAuthorizationTest.php`

Expected: FAIL because `auth:sync-authorization` is unavailable.

- [ ] **Step 3: Implement strict reconciliation and cache invalidation.**

Generate the command with `php artisan make:command SyncAuthorization --no-interaction`. Resolve the catalog, clear Spatie's permission cache before and after writes, create declared permissions and roles, call `syncPermissions` for each declared role, and delete database entries whose names are not catalog values. In dry-run mode, calculate and display the same creates, updates, and deletes without modifying records.

- [ ] **Step 4: Run the command test to verify it passes.**

Run: `php artisan test --compact tests/Feature/Console/SyncAuthorizationTest.php`

Expected: PASS.

- [ ] **Step 5: Commit the completed task.**

```bash
git add app/Console/Commands/SyncAuthorization.php tests/Feature/Console/SyncAuthorizationTest.php
git commit -m "feat: sync authorization catalog"
```

### Task 4: Initialize and protect the Super Admin

**Files:**

- Create: `app/Console/Commands/InitializeSuperAdmin.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Console/InitializeSuperAdminTest.php`
- Test: `tests/Feature/Authorization/SystemUserProtectionTest.php`

**Interfaces:**

- Produces `php artisan auth:init-superadmin {--reset-password}`.
- Produces `User::isSystem(): bool` and public role/deletion protections for system users.
- The command restores or creates the configured user and enforces `Role::SuperAdmin`.

- [ ] **Step 1: Write the failing initialization and protection tests.**

```php
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('initialization preserves an existing system password unless reset is requested', function () {
    config()->set('superadmin.email', 'system@example.test');
    config()->set('superadmin.password', 'password');

    $this->artisan('auth:init-superadmin')->assertSuccessful();
    $systemUser = User::query()->where('email', 'system@example.test')->sole();
    $systemUser->update(['password' => 'changed-password']);

    $this->artisan('auth:init-superadmin')->assertSuccessful();

    expect(Hash::check('changed-password', $systemUser->fresh()->password))->toBeTrue();
});

test('a system user cannot be deleted or have roles changed', function () {
    $systemUser = User::factory()->create(['is_system' => true]);

    expect(fn () => $systemUser->delete())->toThrow(LogicException::class)
        ->and(fn () => $systemUser->syncRoles([]))->toThrow(LogicException::class);
});
```

- [ ] **Step 2: Run the tests to verify they fail.**

Run: `php artisan test --compact tests/Feature/Console/InitializeSuperAdminTest.php tests/Feature/Authorization/SystemUserProtectionTest.php`

Expected: FAIL because the initializer and protection behavior do not exist.

- [ ] **Step 3: Implement initialization and invariants.**

Generate the command with `php artisan make:command InitializeSuperAdmin --no-interaction`. Validate all configured required fields and reject a missing password outside local/testing. Locate an existing system user first, then a configured email including trashed records; restore when needed. Reconcile configured non-secret fields, set `is_system` true, retain the password unless `--reset-password` is supplied, and enforce only `Role::SuperAdmin` through the model's controlled system-role method.

Alias and wrap the relevant public `HasRoles` mutation methods in `User` so system users reject role mutations outside the controlled system-role method. Register a deleting-model callback that throws for a system user. Ensure ordinary users retain Spatie's normal behavior.

- [ ] **Step 4: Run the tests to verify they pass.**

Run: `php artisan test --compact tests/Feature/Console/InitializeSuperAdminTest.php tests/Feature/Authorization/SystemUserProtectionTest.php`

Expected: PASS.

- [ ] **Step 5: Commit the completed task.**

```bash
git add app/Console/Commands/InitializeSuperAdmin.php app/Models/User.php tests/Feature/Console/InitializeSuperAdminTest.php tests/Feature/Authorization/SystemUserProtectionTest.php
git commit -m "feat: initialize protected super admin"
```

### Task 5: Add the Super Admin Gate bypass and complete verification

**Files:**

- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/Authorization/SuperAdminGateTest.php`

**Interfaces:**

- A protected system user with `Role::SuperAdmin` receives `true` for `can()` checks.
- All other users continue through ordinary authorization resolution.

- [ ] **Step 1: Write the failing Gate behavior test.**

```php
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role as PermissionRole;

test('the protected super admin bypasses every Gate ability', function () {
    PermissionRole::findOrCreate(Role::SuperAdmin->value);
    $systemUser = User::factory()->create(['is_system' => true]);
    $systemUser->assignRole(Role::SuperAdmin);

    expect(Gate::forUser($systemUser)->allows('unregistered-ability'))->toBeTrue();
});
```

- [ ] **Step 2: Run the test to verify it fails.**

Run: `php artisan test --compact tests/Feature/Authorization/SuperAdminGateTest.php`

Expected: FAIL because no Gate-before callback grants the bypass.

- [ ] **Step 3: Add the Gate-before callback.**

Add `Gate::before` in `AppServiceProvider::boot()`. Return `true` only when the user is the protected system user and has `Role::SuperAdmin`; return `null` otherwise.

- [ ] **Step 4: Run focused and regression verification.**

Run:

```bash
php artisan test --compact tests/Feature/Authorization/SuperAdminGateTest.php
php artisan test --compact tests/Feature/Authorization tests/Feature/Console
vendor/bin/pint --dirty --format agent
```

Expected: all listed tests PASS and Pint completes without changes remaining.

- [ ] **Step 5: Commit the completed task.**

```bash
git add app/Providers/AppServiceProvider.php tests/Feature/Authorization/SuperAdminGateTest.php
git commit -m "feat: bypass authorization for super admin"
```

### Task 6: Run final targeted quality checks

**Files:**

- Verify: files changed by Tasks 1 through 5

- [ ] **Step 1: Run the complete authorization test set.**

Run: `php artisan test --compact tests/Unit/Authorization tests/Feature/Authorization tests/Feature/Console`

Expected: PASS.

- [ ] **Step 2: Run static analysis for the changed PHP.**

Run: `vendor/bin/phpstan analyse --memory-limit=1G --no-progress`

Expected: PASS with no new errors.

- [ ] **Step 3: Inspect the final diff.**

Run: `git diff --check && git diff -- app config database tests`

Expected: no whitespace errors; only the authorization implementation and its tests are changed.

- [ ] **Step 4: Commit final formatting changes if any.**

```bash
git add app config database tests
git commit -m "style: format authorization changes"
```
