<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateGeneralSettings;
use App\Enums\SiteLocale;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateGeneralSettingsRequest;
use App\Settings\GeneralSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GeneralSettingsController extends Controller
{
    /**
     * Show the general settings form.
     */
    public function edit(GeneralSettings $generalSettings): Response
    {
        return Inertia::render('settings/general/Edit', [
            'generalSettings' => [
                'site_name' => $generalSettings->site_name,
                'site_description' => $generalSettings->site_description,
                'site_locale' => $generalSettings->site_locale,
            ],
            'localeOptions' => SiteLocale::options(),
        ]);
    }

    /**
     * Update the general settings.
     */
    public function update(
        UpdateGeneralSettingsRequest $request,
        GeneralSettings $generalSettings,
        UpdateGeneralSettings $updateGeneralSettings
    ): RedirectResponse {
        $updateGeneralSettings->handle($generalSettings, $request->payload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('settings.general.toast.updated')]);

        return to_route('settings.general.edit');
    }
}
