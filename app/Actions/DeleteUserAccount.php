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
