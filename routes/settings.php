<?php

use App\Http\Controllers\Settings\GeneralSettingsController;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('settings', 'settings/Index')
        ->name('settings.index')
        ->can('view', GeneralSettings::class);

    Route::get('settings/general', [GeneralSettingsController::class, 'edit'])
        ->name('settings.general.edit')
        ->can('view', GeneralSettings::class);

    Route::patch('settings/general', [GeneralSettingsController::class, 'update'])
        ->middleware(['precognitive', 'throttle:30,1'])
        ->name('settings.general.update')
        ->can('update', GeneralSettings::class);
});
