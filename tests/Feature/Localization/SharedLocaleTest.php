<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

test('the active locale is shared with inertia responses', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('locale', app()->getLocale()));
});
