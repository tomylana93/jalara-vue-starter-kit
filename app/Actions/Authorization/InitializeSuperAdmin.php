<?php

namespace App\Actions\Authorization;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Models\Role as PermissionRole;

final class InitializeSuperAdmin
{
    /**
     * Initialize or restore the protected Super Admin user and enforce its role.
     *
     * @param  array{name: string, email: string, phone: ?string, status: UserStatus, email_verified: bool, password: ?string}  $attributes
     */
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
}
