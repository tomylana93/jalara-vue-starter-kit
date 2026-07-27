<?php

namespace App\Enums;

enum SiteAuthLayout: string
{
    case Simple = 'simple';
    case Split = 'split';
    case Card = 'card';

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(static fn (self $option): array => [
            'value' => $option->value,
            'label' => (string) __("style.options.auth_layout.{$option->value}"),
        ], self::cases());
    }
}
