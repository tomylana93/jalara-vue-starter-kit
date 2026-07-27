<?php

namespace App\Enums;

enum SiteFont: string
{
    case Inter = 'inter';
    case SoraInter = 'sora-inter';
    case PlusJakartaDmSans = 'plus-jakarta-dm-sans';
    case SpaceGroteskInter = 'space-grotesk-inter';
    case NunitoPlusJakarta = 'nunito-plus-jakarta';

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(static fn (self $option): array => [
            'value' => $option->value,
            'label' => (string) __("style.options.font.{$option->value}"),
        ], self::cases());
    }
}
