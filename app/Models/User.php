<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Support\MediaDisk;
use BackedEnum;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use LogicException;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role as PermissionRole;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|CarbonImmutable|null $email_verified_at
 * @property string|null $phone
 * @property UserStatus $status
 * @property bool $is_system
 * @property string $password
 * @property bool $must_change_password
 * @property Carbon|CarbonImmutable|null $last_login_at
 * @property int $failed_login_attempts
 * @property Carbon|CarbonImmutable|null $suspended_until
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|CarbonImmutable|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|CarbonImmutable|null $created_at
 * @property Carbon|CarbonImmutable|null $updated_at
 * @property Carbon|CarbonImmutable|null $deleted_at
 */
#[Fillable(['name', 'email', 'phone', 'status', 'password', 'must_change_password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements HasMedia, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, InteractsWithMedia, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    use HasRoles {
        assignRole as protected spatieAssignRole;
        removeRole as protected spatieRemoveRole;
        syncRoles as protected spatieSyncRoles;
        roles as protected spatieRoles;
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
            throw_if($user->isSystem(), LogicException::class, 'System users cannot be deleted.');
        });

        static::updating(function (User $user): void {
            throw_if($user->isDirty('is_system'), LogicException::class, 'The system status of a user cannot be changed directly.');
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
     *
     * @param  string|int|array<array-key, mixed>|\Spatie\Permission\Contracts\Role|Collection<array-key, mixed>|BackedEnum  ...$roles
     */
    public function assignRole(mixed ...$roles): static
    {
        $this->guardSystemRoleMutation();

        return $this->spatieAssignRole(...$roles);
    }

    /**
     * Remove the given role(s) from the user, rejecting mutation for system users.
     *
     * @param  string|int|array<array-key, mixed>|\Spatie\Permission\Contracts\Role|Collection<array-key, mixed>|BackedEnum  ...$role
     */
    public function removeRole(mixed ...$role): static
    {
        $this->guardSystemRoleMutation();

        return $this->spatieRemoveRole(...$role);
    }

    /**
     * Sync the given role(s) on the user, rejecting mutation for system users.
     *
     * @param  string|int|array<array-key, mixed>|\Spatie\Permission\Contracts\Role|Collection<array-key, mixed>|BackedEnum  ...$roles
     */
    public function syncRoles(mixed ...$roles): static
    {
        $this->guardSystemRoleMutation();

        return $this->spatieSyncRoles(...$roles);
    }

    /**
     * Enforce the Super Admin role for this system user, bypassing the public mutation guard.
     *
     * This is the only sanctioned way to change a system user's roles, intended
     * for use by the Super Admin initializer.
     */
    public function enforceSuperAdminRole(): void
    {
        throw_unless($this->isSystem(), LogicException::class, 'Only system users can enforce the Super Admin role.');

        $this->bypassSystemRoleGuard = true;

        try {
            $this->spatieSyncRoles(Role::SuperAdmin);
        } finally {
            $this->bypassSystemRoleGuard = false;
        }
    }

    /**
     * Guard against role mutation on a protected system user.
     */
    public function guardSystemRoleMutation(): void
    {
        throw_if($this->isSystem() && ! $this->bypassSystemRoleGuard, LogicException::class, 'Roles for system users cannot be changed directly.');
    }

    /**
     * Get the roles relationship, guarded against unauthorized mutations.
     *
     * @return GuardedSystemRoleMorphToMany
     */
    public function roles(): MorphToMany
    {
        $relation = $this->spatieRoles();

        return new GuardedSystemRoleMorphToMany(PermissionRole::query(), $this, 'model', $relation->getTable(), $relation->getForeignPivotKeyName(), $relation->getRelatedPivotKeyName(), $relation->getParentKeyName(), $relation->getRelatedKeyName(), $relation->getRelationName(), false);
    }

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
            ->performOnCollections('avatar')
            ->queued()
            ->format('webp')
            ->fit(Fit::Crop, 256, 256);
    }

    public function avatarUrl(): ?string
    {
        $avatar = $this->getFirstMedia('avatar');

        if (! $avatar instanceof Media) {
            return null;
        }

        return $avatar->hasGeneratedConversion('avatar')
            ? $avatar->getUrl('avatar')
            : $avatar->getUrl();
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
