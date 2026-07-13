<?php

use App\Models\User;

test('a system user cannot be deleted or have roles changed', function () {
    $systemUser = User::factory()->create(['is_system' => true]);

    expect(fn () => $systemUser->delete())->toThrow(LogicException::class)
        ->and(fn () => $systemUser->syncRoles([]))->toThrow(LogicException::class);
});
