<?php

use App\Enums\SiteLocale;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'Jalara Vue Starter Kit');
        $this->migrator->add('general.site_description', 'Jalara provides a structured application foundation intended for projects that value modularity, flexibility, modern tooling, and clear organization.');
        $this->migrator->add('general.site_locale', SiteLocale::English->value);
    }
};
