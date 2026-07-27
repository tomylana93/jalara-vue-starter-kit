<?php

namespace App\Actions\Fortify;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Fortify;

class AuthenticateUser
{
    /**
     * Attempt to authenticate the incoming login request against the account policy.
     *
     * Returns the user only when the credentials are valid and the account is
     * eligible to authenticate. Every rejected attempt returns null so Fortify
     * emits its generic failure response without disclosing account state.
     */
    public function __invoke(Request $request): ?User
    {
        $username = Fortify::username();

        $user = User::withTrashed()
            ->where($username, $request->input($username))
            ->first();

        if (! $user || $user->trashed() || $user->status === UserStatus::Disable) {
            return null;
        }

        if ($user->status === UserStatus::Suspend) {
            if ($user->suspended_until === null || $user->suspended_until->isFuture()) {
                return null;
            }

            $this->restoreExpiredSuspension($user);
        }

        if (! Hash::check((string) $request->input('password'), $user->password)) {
            $this->registerFailedAttempt($user);

            return null;
        }

        if ($user->failed_login_attempts > 0) {
            $user->forceFill(['failed_login_attempts' => 0])->save();
        }

        return $user;
    }

    /**
     * Restore a user whose automatic suspension has expired to an active state.
     */
    private function restoreExpiredSuspension(User $user): void
    {
        $user->forceFill([
            'status' => UserStatus::Active,
            'suspended_until' => null,
            'failed_login_attempts' => 0,
        ])->save();
    }

    /**
     * Atomically record a failed password attempt and suspend the user when the
     * configured threshold is reached.
     */
    private function registerFailedAttempt(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $locked = User::withTrashed()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->trashed() || $locked->status !== UserStatus::Active) {
                return;
            }

            $attempts = $locked->failed_login_attempts + 1;
            $maxAttempts = (int) config('auth.login_security.max_failed_attempts');

            $attributes = ['failed_login_attempts' => $attempts];

            if ($attempts >= $maxAttempts) {
                $attributes['status'] = UserStatus::Suspend;
                $attributes['suspended_until'] = now()->addMinutes(
                    (int) config('auth.login_security.suspension_minutes')
                );
            }

            $locked->forceFill($attributes)->save();
        });
    }
}
