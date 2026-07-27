<?php

use App\Http\Controllers\Settings\BrandingUploadController;
use App\Http\Controllers\Settings\GeneralSettingsController;
use App\Http\Controllers\Settings\StyleSettingsController;
use App\Settings\GeneralSettings;
use App\Settings\StyleSettings;
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

    Route::get('settings/style', [StyleSettingsController::class, 'edit'])
        ->name('settings.style.edit')
        ->can('view', StyleSettings::class);

    Route::patch('settings/style', [StyleSettingsController::class, 'update'])
        ->middleware(['precognitive', 'throttle:30,1'])
        ->name('settings.style.update')
        ->can('update', StyleSettings::class);

    Route::post('settings/style/uploads/{field}', [BrandingUploadController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('settings.style.uploads.store');

    Route::delete('settings/style/uploads/{temporaryUpload}', [BrandingUploadController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('settings.style.uploads.destroy');
});
