<?php

use App\Actions\DeleteUserAccount;
use App\Models\User;

test('it deletes the user account', function () {
    $user = User::factory()->create();

    (new DeleteUserAccount)->handle($user);

    expect($user->fresh())->toBeNull();
});
