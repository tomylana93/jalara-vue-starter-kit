<?php

namespace App\Http\Requests\Settings;

use App\Enums\SiteAuthLayout;
use App\Enums\SiteFont;
use App\Enums\SiteLayout;
use App\Enums\SiteLogoStyle;
use App\Enums\SiteTheme;
use App\Settings\StyleSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStyleSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', StyleSettings::class) ?? false;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        $rules = [
            'site_logo_style' => ['required', Rule::enum(SiteLogoStyle::class)],
            'site_auth_layout' => ['required', Rule::enum(SiteAuthLayout::class)],
            'site_layout' => ['required', Rule::enum(SiteLayout::class)],
            'site_theme' => ['required', Rule::enum(SiteTheme::class)],
            'site_font' => ['required', Rule::enum(SiteFont::class)],
        ];

        foreach ($this->assetFields() as $field) {
            $rules["{$field}_upload_id"] = ['nullable', 'uuid'];
            $rules["{$field}_remove"] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    /** @return array<string, bool|string|null> */
    public function payload(): array
    {
        $payload = [
            'site_logo_style' => $this->string('site_logo_style')->toString(),
            'site_auth_layout' => $this->string('site_auth_layout')->toString(),
            'site_layout' => $this->string('site_layout')->toString(),
            'site_theme' => $this->string('site_theme')->toString(),
            'site_font' => $this->string('site_font')->toString(),
        ];

        foreach ($this->assetFields() as $field) {
            $payload["{$field}_upload_id"] = $this->filled("{$field}_upload_id")
                ? $this->string("{$field}_upload_id")->toString()
                : null;
            $payload["{$field}_remove"] = $this->boolean("{$field}_remove");
        }

        return $payload;
    }

    /** @return list<string> */
    private function assetFields(): array
    {
        return ['icon', 'icon_dark', 'logo', 'logo_dark', 'favicon', 'auth_split_background'];
    }
}
