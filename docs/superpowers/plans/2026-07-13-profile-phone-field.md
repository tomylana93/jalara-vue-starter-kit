# Profile Phone Field Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans task-by-task. Steps use checkbox syntax for tracking.

**Goal:** Allow users to add, update, and clear their optional, unique phone number from Profile settings.

**Architecture:** Extend the existing profile request/action pipeline with `phone`. The Form Request validates and converts blank input to `null`; the existing action fills and saves it; the existing Inertia form renders the input and validation error. No route, migration, dependency, or new component is needed.

**Tech Stack:** PHP 8.5, Laravel 13, Inertia v3, Vue 3, TypeScript, Pest 4, Pint, ESLint, Prettier.

## Global Constraints

- Keep `phone` optional; persist blank input as `null`.
- Preserve the existing unique `users.phone` index; create no migration.
- Reject another user's phone, while allowing the current user's own value.
- Do not add E.164 formatting, SMS, verification, or phone authentication.
- Phone-only updates must not reset `email_verified_at`.
- Preserve the existing profile routes, Inertia `<Form>`, and success toast.

---

### Task 1: Add request behavior and backend coverage

**Files:**
- Modify: `app/Concerns/ProfileValidationRules.php`
- Modify: `app/Http/Requests/Settings/ProfileUpdateRequest.php`
- Modify: `app/Actions/UpdateUserProfile.php`
- Modify: `tests/Feature/Settings/ProfileUpdateTest.php`
- Modify: `tests/Unit/Auth/Actions/UpdateUserProfileTest.php`

**Interfaces:**
- Produces `phoneRules(int|string|null $userId = null): array`.
- Changes `profileAttributes()` to `array{name: string, email: string, phone: ?string}`.

- [ ] **Step 1: Write failing profile feature tests**

Add tests that PATCH `profile.update` and assert: an unused `+628111111111` persists; an existing `+628122222222` becomes `null` when `phone` is `''`; and a second user holding `+628133333333` causes `assertSessionHasErrors('phone')`. Include unchanged name/email in every request, assert redirects to `profile.edit`, and assert the successful phone-only update leaves `email_verified_at` non-null.

- [ ] **Step 2: Run the failing test**

```bash
php artisan test --compact tests/Feature/Settings/ProfileUpdateTest.php
```

Expected: FAIL because the current request excludes `phone`.

- [ ] **Step 3: Add server validation and normalization**

Add this `profileRules()` entry and method in `ProfileValidationRules`:

```php
'phone' => $this->phoneRules($userId),

protected function phoneRules(int|string|null $userId = null): array
{
    return [
        'nullable',
        'string',
        'max:255',
        $userId === null
            ? Rule::unique(User::class, 'phone')
            : Rule::unique(User::class, 'phone')->ignore($userId),
    ];
}
```

Replace `ProfileUpdateRequest::profileAttributes()` with this shape and payload:

```php
/** @return array{name: string, email: string, phone: ?string} */
public function profileAttributes(): array
{
    return [
        'name' => $this->string('name')->toString(),
        'email' => $this->string('email')->toString(),
        'phone' => $this->filled('phone') ? $this->string('phone')->toString() : null,
    ];
}
```

- [ ] **Step 4: Align action contract and unit test**

Change `UpdateUserProfile` PHPDoc to `array{name: string, email: string, phone: ?string}`. Pass a `phone` key in all existing action-test calls, and extend the update test with:

```php
->and($updatedUser->phone)->toBe('+628144444444');
```

The action’s `isDirty('email')` conditional must stay unchanged.

- [ ] **Step 5: Verify backend behavior**

```bash
php artisan test --compact tests/Feature/Settings/ProfileUpdateTest.php tests/Unit/Auth/Actions/UpdateUserProfileTest.php
vendor/bin/pint --dirty --format agent
```

Expected: PASS; blank values clear, duplicates fail, and email verification is preserved for phone-only updates.

### Task 2: Expose and render the phone field

**Files:**
- Modify: `resources/js/types/auth.ts`
- Modify: `resources/js/pages/settings/Profile.vue`

**Interfaces:**
- Produces `User.phone: string | null`.
- Consumes `page.props.auth.user.phone` and `errors.phone`.

- [ ] **Step 1: Type the shared field**

Insert `phone: string | null;` directly after `email` in `resources/js/types/auth.ts`. Do not change the unrelated `id` type or index signature.

- [ ] **Step 2: Add the form control**

Update the heading description to mention phone number. After the email block in `Profile.vue`, add:

```vue
<div class="grid gap-2">
    <Label for="phone">Phone number</Label>
    <Input id="phone" type="tel" class="mt-1 block w-full" name="phone" :default-value="user.phone ?? ''" autocomplete="tel" placeholder="Phone number" />
    <InputError class="mt-2" :message="errors.phone" />
</div>
```

Use the existing `Form`, components, and classes; do not add client-side format validation.

- [ ] **Step 3: Run frontend static checks**

```bash
pnpm run format:check
pnpm run lint:check
pnpm run types:check
```

Expected: PASS and `user.phone` is type-safe.

### Task 3: Final verification and commit

**Files:** Inspect all files listed in Tasks 1–2.

- [ ] **Step 1: Run final checks**

```bash
php artisan test --compact tests/Feature/Settings/ProfileUpdateTest.php tests/Unit/Auth/Actions/UpdateUserProfileTest.php
git diff --check
```

Expected: PASS with no whitespace errors.

- [ ] **Step 2: Confirm scope**

```bash
git diff -- app/Concerns/ProfileValidationRules.php app/Http/Requests/Settings/ProfileUpdateRequest.php app/Actions/UpdateUserProfile.php resources/js/types/auth.ts resources/js/pages/settings/Profile.vue tests/Feature/Settings/ProfileUpdateTest.php tests/Unit/Auth/Actions/UpdateUserProfileTest.php
```

Expected: only the approved profile-phone behavior; no migration, route, Wayfinder, dependency, or unrelated type change.

- [ ] **Step 3: Commit**

```bash
git add app/Concerns/ProfileValidationRules.php app/Http/Requests/Settings/ProfileUpdateRequest.php app/Actions/UpdateUserProfile.php resources/js/types/auth.ts resources/js/pages/settings/Profile.vue tests/Feature/Settings/ProfileUpdateTest.php tests/Unit/Auth/Actions/UpdateUserProfileTest.php && git commit -m "fix: add phone field to profile settings"
```

Expected: one focused implementation commit after the specification commit.
