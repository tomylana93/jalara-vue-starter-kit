<?php

namespace App\Actions\Profile;

use App\Models\User;

final class RemoveUserAvatar
{
    /**
     * Clear the user's avatar media collection.
     */
    public function handle(User $user): void
    {
        $user->clearMediaCollection('avatar');
    }
}
