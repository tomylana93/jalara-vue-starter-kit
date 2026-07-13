<?php

use Illuminate\Support\Facades\Schema;

/*
 * The test database is SQLite, which reports UUID columns as `varchar`.
 * Asserting `varchar` (rather than the old integer keys) proves the identity
 * and dependent keys are UUID-compatible string columns.
 */
test('user identity and dependent keys use uuid-compatible columns', function () {
    expect(Schema::getColumnType('users', 'id'))->toBe('varchar')
        ->and(Schema::getColumnType('sessions', 'user_id'))->toBe('varchar')
        ->and(Schema::getColumnType('passkeys', 'user_id'))->toBe('varchar');
});

test('users contain the new domain columns', function () {
    expect(Schema::hasColumns('users', [
        'phone', 'status', 'must_change_password', 'last_login_at', 'deleted_at',
    ]))->toBeTrue();
});
