<?php

use App\Actions\Profile\DeleteTemporaryAvatarUpload;
use App\Actions\Profile\TemporaryAvatarDeletionResult;
use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use App\Models\User;

test('it reports deleted, missing, and forbidden outcomes', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownedUpload = TemporaryUpload::factory()->for($owner)->create(['purpose' => TemporaryUploadPurpose::Avatar]);
    $foreignUpload = TemporaryUpload::factory()->for($otherUser)->create(['purpose' => TemporaryUploadPurpose::Avatar]);
    $action = app(DeleteTemporaryAvatarUpload::class);

    expect($action->handle($owner, $ownedUpload->id))->toBe(TemporaryAvatarDeletionResult::Deleted)
        ->and($ownedUpload->fresh())->toBeNull()
        ->and($action->handle($owner, $ownedUpload->id))->toBe(TemporaryAvatarDeletionResult::Missing)
        ->and($action->handle($owner, $foreignUpload->id))->toBe(TemporaryAvatarDeletionResult::Forbidden)
        ->and($foreignUpload->fresh())->not->toBeNull();
});
