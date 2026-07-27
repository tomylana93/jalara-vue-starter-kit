<?php

use App\Enums\SiteAuthLayout;
use App\Enums\SiteFont;
use App\Enums\SiteLayout;
use App\Enums\SiteLogoStyle;
use App\Enums\SiteTheme;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('style.site_logo_style', SiteLogoStyle::Icon->value);
        $this->migrator->add('style.site_auth_layout', SiteAuthLayout::Simple->value);
        $this->migrator->add('style.site_layout', SiteLayout::Sidebar->value);
        $this->migrator->add('style.site_theme', SiteTheme::Zinc->value);
        $this->migrator->add('style.site_font', SiteFont::Inter->value);
    }
};
