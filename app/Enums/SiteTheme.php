<?php

namespace App\Enums;

enum SiteTheme: string
{
    case Zinc = 'zinc';
    case Slate = 'slate';
    case Emerald = 'emerald';
    case Rose = 'rose';
    case Indigo = 'indigo';
    case Violet = 'violet';
    case Cyan = 'cyan';
    case Orange = 'orange';
    case Teal = 'teal';
    case Fuchsia = 'fuchsia';

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(static fn (self $option): array => [
            'value' => $option->value,
            'label' => (string) __("style.options.theme.{$option->value}"),
        ], self::cases());
    }
}
