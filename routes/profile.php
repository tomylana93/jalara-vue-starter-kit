<?php

use App\Http\Controllers\Profile\AvatarUploadController;
use App\Http\Controllers\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('profile/avatar-uploads', [AvatarUploadController::class, 'store'])->name('profile.avatar-uploads.store');
    Route::delete('profile/avatar-uploads/{temporaryAvatarUpload}', [AvatarUploadController::class, 'destroy'])->name('profile.avatar-uploads.destroy');
    Route::delete('profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::patch('profile', [ProfileController::class, 'update'])
        ->middleware(['precognitive', 'throttle:30,1'])
        ->name('profile.update');
});
