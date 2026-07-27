<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class StyleSettings extends Settings
{
    public string $site_logo_style;

    public string $site_auth_layout;

    public string $site_layout;

    public string $site_theme;

    public string $site_font;

    public static function group(): string
    {
        return 'style';
    }
}
