<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $phone
 * @property UserStatus $status
 * @property bool $is_system
 * @property string $password
 * @property bool $must_change_password
 * @property Carbon|null $last_login_at
 * @property int $failed_login_attempts
 * @property Carbon|null $suspended_until
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'email', 'phone', 'status', 'password', 'must_change_password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    use HasRoles {
        assignRole as protected spatieAssignRole;
        removeRole as protected spatieRemoveRole;
        syncRoles as protected spatieSyncRoles;
    }

    /**
     * Whether the system role-mutation guard is temporarily bypassed.
     */
    private bool $bypassSystemRoleGuard = false;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            if ($user->isSystem()) {
                throw new \LogicException('System users cannot be deleted.');
            }
        });
    }

    /**
     * Determine whether this user is a protected system user.
     */
    public function isSystem(): bool
    {
        return $this->is_system === true;
    }

    /**
     * Assign the given role(s) to the user, rejecting mutation for system users.
     */
    public function assignRole(...$roles): static
    {
        $this->guardSystemRoleMutation();

        return $this->spatieAssignRole(...$roles);
    }

    /**
     * Remove the given role(s) from the user, rejecting mutation for system users.
     */
    public function removeRole(...$role): static
    {
        $this->guardSystemRoleMutation();

        return $this->spatieRemoveRole(...$role);
    }

    /**
     * Sync the given role(s) on the user, rejecting mutation for system users.
     */
    public function syncRoles(...$roles): static
    {
        $this->guardSystemRoleMutation();

        return $this->spatieSyncRoles(...$roles);
    }

    /**
     * Apply the system role to this user, bypassing the public mutation guard.
     *
     * This is the only sanctioned way to change a system user's roles, intended
     * for use by the Super Admin initializer.
     */
    public function applySystemRole(Role $role): void
    {
        $this->bypassSystemRoleGuard = true;

        try {
            $this->spatieSyncRoles($role);
        } finally {
            $this->bypassSystemRoleGuard = false;
        }
    }

    /**
     * Guard against role mutation on a protected system user.
     */
    private function guardSystemRoleMutation(): void
    {
        if ($this->isSystem() && ! $this->bypassSystemRoleGuard) {
            throw new \LogicException('Roles for system users cannot be changed directly.');
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'status' => UserStatus::class,
            'is_system' => 'boolean',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'failed_login_attempts' => 'integer',
            'suspended_until' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
