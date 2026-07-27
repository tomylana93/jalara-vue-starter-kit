<?php

namespace App\Enums;

enum SiteLocale: string
{
    case English = 'en';
    case Indonesian = 'id';

    /**
     * Get the selectable locale options for forms.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(static fn (self $locale): array => [
            'value' => $locale->value,
            'label' => (string) __("general.locale.{$locale->value}"),
        ], self::cases());
    }
}
