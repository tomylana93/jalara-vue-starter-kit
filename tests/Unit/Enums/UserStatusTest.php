<?php

use App\Enums\UserStatus;

test('options returns label and value for each case', function () {
    expect(UserStatus::options())->toBe([
        ['label' => 'Active', 'value' => 'active'],
        ['label' => 'Disabled', 'value' => 'disable'],
        ['label' => 'Suspended', 'value' => 'suspend'],
    ]);
});

test('options maps additional fields to case methods', function () {
    $options = UserStatus::options(['variant' => 'variant']);

    expect($options[0])->toBe([
        'label' => 'Active',
        'value' => 'active',
        'variant' => 'default',
    ]);
});

test('options throws when an additional field method does not exist', function () {
    UserStatus::options(['missing' => 'doesNotExist']);
})->throws(LogicException::class);
