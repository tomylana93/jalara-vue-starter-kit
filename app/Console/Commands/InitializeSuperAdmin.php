<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role as PermissionRole;

#[Signature('auth:init-superadmin {--reset-password}')]
#[Description('Initialize or restore the protected Super Admin user and enforce its role.')]
class InitializeSuperAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = config('superadmin.name');
        $email = config('superadmin.email');
        $phone = config('superadmin.phone');
        $status = config('superadmin.status');
        $emailVerified = config('superadmin.email_verified');
        $password = config('superadmin.password');

        if (! $name || ! $email || ! $status) {
            $this->components->error('The superadmin.name, superadmin.email, and superadmin.status configuration values are required.');

            return self::FAILURE;
        }

        if (empty($password) && ! app()->environment(['local', 'testing'])) {
            $this->components->error('SUPERADMIN_PASSWORD must be set in this environment before the Super Admin can be initialized.');

            return self::FAILURE;
        }

        $user = User::withTrashed()->where('is_system', true)->first()
            ?? User::withTrashed()->where('email', $email)->first();

        $isNewUser = $user === null;

        if ($isNewUser) {
            $user = new User;
        } elseif ($user->trashed()) {
            $user->restore();
        }

        $user->name = $name;
        $user->email = $email;
        $user->phone = $phone;
        $user->status = $status instanceof UserStatus ? $status : UserStatus::from($status);
        $user->is_system = true;

        if ($emailVerified && $user->email_verified_at === null) {
            $user->email_verified_at = Carbon::now();
        }

        if ($isNewUser || $this->option('reset-password')) {
            $user->password = $password;
        }

        $user->save();

        PermissionRole::findOrCreate(Role::SuperAdmin->value);

        $user->applySystemRole(Role::SuperAdmin);

        $this->components->info("Super Admin initialized for [{$user->email}].");

        return self::SUCCESS;
    }
}
