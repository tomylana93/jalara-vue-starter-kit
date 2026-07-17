<?php

use App\Models\User;

test('password confirmation page hydrates without browser errors', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit(route('password.confirm'))
        ->assertSee('Confirm password')
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();
});
