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
