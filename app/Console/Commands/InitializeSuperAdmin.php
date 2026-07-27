<?php

namespace App\Console\Commands;

use App\Actions\Authorization\InitializeSuperAdmin as InitializeSuperAdminAction;
use App\Enums\UserStatus;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('auth:init-superadmin {--reset-password}')]
#[Description('Initialize or restore the protected Super Admin user and enforce its role.')]
class InitializeSuperAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(InitializeSuperAdminAction $initializeSuperAdmin): int
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
            $this->components->error('SUPER_ADMIN_PASSWORD must be set in this environment before the Super Admin can be initialized.');

            return self::FAILURE;
        }

        $user = $initializeSuperAdmin->handle([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'status' => $status instanceof UserStatus ? $status : UserStatus::from($status),
            'email_verified' => (bool) $emailVerified,
            'password' => is_string($password) ? $password : null,
        ], (bool) $this->option('reset-password'));

        $this->components->info("Super Admin initialized for [{$user->email}].");

        return self::SUCCESS;
    }
}
