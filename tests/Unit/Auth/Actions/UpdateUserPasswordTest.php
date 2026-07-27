<?php

use App\Actions\UpdateUserPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it updates the user password', function () {
    $user = User::factory()->create();

    (new UpdateUserPassword)->handle($user, 'new-password');

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});
