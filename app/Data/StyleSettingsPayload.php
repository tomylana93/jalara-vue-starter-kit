<?php

namespace App\Data;

/**
 * @phpstan-type StyleSettingsData array{
 *   site_logo_style: string,
 *   site_auth_layout: string,
 *   site_layout: string,
 *   site_theme: string,
 *   site_font: string,
 *   icon_upload_id?: string|null,
 *   icon_remove?: bool,
 *   icon_dark_upload_id?: string|null,
 *   icon_dark_remove?: bool,
 *   logo_upload_id?: string|null,
 *   logo_remove?: bool,
 *   logo_dark_upload_id?: string|null,
 *   logo_dark_remove?: bool,
 *   favicon_upload_id?: string|null,
 *   favicon_remove?: bool,
 *   auth_split_background_upload_id?: string|null,
 *   auth_split_background_remove?: bool
 * }
 */
final readonly class StyleSettingsPayload
{
    /** @param StyleSettingsData $values */
    public function __construct(public array $values) {}

    /** @param StyleSettingsData $values */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }
}
