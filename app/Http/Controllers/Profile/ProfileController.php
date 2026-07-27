<?php

namespace App\Http\Controllers\Profile;

use App\Actions\Profile\RemoveUserAvatar;
use App\Actions\PromoteTemporaryAvatarUpload;
use App\Actions\UpdateUserProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $avatar = $request->user()->getFirstMedia('avatar');
        $avatarUrl = $request->user()->avatarUrl();

        return Inertia::render('Profile', [
            'avatar' => $avatar === null || $avatarUrl === null ? null : [
                'id' => $avatar->id,
                'source' => $avatarUrl,
                'name' => $avatar->file_name,
                'size' => $avatar->size,
                'type' => $avatar->mime_type,
            ],
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(
        ProfileUpdateRequest $request,
        UpdateUserProfile $updateUserProfile,
        PromoteTemporaryAvatarUpload $promoteTemporaryAvatarUpload
    ): RedirectResponse {
        try {
            $promoteTemporaryAvatarUpload->handle($request->user(), $request->temporaryAvatarUploadId());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            return back()->withErrors([
                'temporary_avatar_upload_id' => __('profile.error.temporary_avatar_unavailable'),
            ]);
        }

        $updateUserProfile->handle($request->user(), $request->profileAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('profile.toast.updated')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's avatar.
     */
    public function destroyAvatar(Request $request, RemoveUserAvatar $removeUserAvatar): RedirectResponse
    {
        $removeUserAvatar->handle($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('profile.toast.avatar_removed')]);

        return to_route('profile.edit');
    }
}
