<?php

namespace App\Enums;

enum SiteLogoStyle: string
{
    case Icon = 'icon';
    case Logo = 'logo';

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(static fn (self $option): array => [
            'value' => $option->value,
            'label' => (string) __("style.options.logo_style.{$option->value}"),
        ], self::cases());
    }
}
