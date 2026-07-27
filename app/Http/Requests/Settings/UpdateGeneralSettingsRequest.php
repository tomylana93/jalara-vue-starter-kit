<?php

namespace App\Http\Requests\Settings;

use App\Enums\SiteLocale;
use App\Settings\GeneralSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneralSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', GeneralSettings::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:255'],
            'site_description' => ['nullable', 'string', 'max:1000'],
            'site_locale' => ['required', Rule::enum(SiteLocale::class)],
        ];
    }

    /**
     * Get the validated general settings payload with its guaranteed shape.
     *
     * @return array{site_name: string, site_description: string, site_locale: string}
     */
    public function payload(): array
    {
        return [
            'site_name' => $this->string('site_name')->toString(),
            'site_description' => $this->filled('site_description') ? $this->string('site_description')->toString() : '',
            'site_locale' => $this->string('site_locale')->toString(),
        ];
    }
}
