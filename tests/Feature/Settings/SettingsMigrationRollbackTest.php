<?php

use Illuminate\Support\Facades\Schema;

function settingsMigration(): object
{
    return require database_path('migrations/2022_12_14_083707_create_settings_table.php');
}

function settingsTableName(): string
{
    $table = config('settings.repositories.database.table');

    return is_string($table) ? $table : 'settings';
}

test('the settings migration rolls back the table', function (): void {
    $table = settingsTableName();

    expect(Schema::hasTable($table))->toBeTrue();

    settingsMigration()->down();

    expect(Schema::hasTable($table))->toBeFalse();
});

test('the settings migration can be reapplied after a rollback', function (): void {
    $table = settingsTableName();
    $migration = settingsMigration();

    $migration->down();

    expect(Schema::hasTable($table))->toBeFalse();

    $migration->up();

    expect(Schema::hasTable($table))->toBeTrue()
        ->and(Schema::hasColumns($table, ['group', 'name', 'locked', 'payload']))->toBeTrue();
});
