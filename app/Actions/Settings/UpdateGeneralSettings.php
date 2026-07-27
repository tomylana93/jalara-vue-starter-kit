<?php

namespace App\Actions\Settings;

use App\Settings\GeneralSettings;

final class UpdateGeneralSettings
{
    /**
     * @param  array{site_name: string, site_description: string, site_locale: string}  $data
     */
    public function handle(GeneralSettings $generalSettings, array $data): void
    {
        $generalSettings->site_name = $data['site_name'];
        $generalSettings->site_description = $data['site_description'];
        $generalSettings->site_locale = $data['site_locale'];
        $generalSettings->save();
    }
}
