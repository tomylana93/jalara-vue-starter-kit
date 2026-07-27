<?php

test('the login page renders without browser or accessibility errors', function (): void {
    $page = visit('/login');

    $page->assertSee('email')
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs()
        ->assertNoAccessibilityIssues();
});
