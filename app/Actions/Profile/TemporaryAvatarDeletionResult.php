<?php

namespace App\Actions\Profile;

enum TemporaryAvatarDeletionResult
{
    case Deleted;
    case Missing;
    case Forbidden;
}
