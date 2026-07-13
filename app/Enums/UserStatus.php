<?php

namespace App\Enums;

use App\Concerns\HasOptions;

enum UserStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Disable = 'disable';
    case Suspend = 'suspend';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Disable => 'Disabled',
            self::Suspend => 'Suspended',
        };
    }

    /**
     * Get the message associated with the status.
     */
    public function message(): string
    {
        return match ($this) {
            self::Active => __('auth.login.message.active'),
            self::Disable => __('auth.login.message.disable'),
            self::Suspend => __('auth.login.message.suspend'),
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Active => 'default',
            self::Disable => 'destructive',
            self::Suspend => 'secondary',
        };
    }
}
