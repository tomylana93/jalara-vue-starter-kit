<?php

namespace App\Enums;

enum SiteLayout: string
{
    case Sidebar = 'sidebar';
    case Header = 'header';

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(static fn (self $option): array => [
            'value' => $option->value,
            'label' => (string) __("style.options.layout.{$option->value}"),
        ], self::cases());
    }
}
