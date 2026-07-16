<?php

namespace App\Http\Requests\Settings;

use App\Settings\StyleSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreBrandingUploadRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['field' => $this->route('field')]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('update', StyleSettings::class) ?? false;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'field' => ['required', Rule::in([
                'icon', 'icon_dark', 'logo', 'logo_dark', 'favicon',
                'auth_split_background',
            ])],
            'file' => $this->fileRules(),
        ];
    }

    /** @return array<mixed> */
    private function fileRules(): array
    {
        return match ($this->string('field')->toString()) {
            'favicon' => ['required', File::types(['png', 'webp', 'ico'])->max(1024)],
            'auth_split_background' => [
                'required',
                File::types(['jpeg', 'jpg', 'webp'])->max(5 * 1024),
                'dimensions:min_width=1200,min_height=800',
            ],
            default => ['required', File::types(['png', 'jpeg', 'jpg', 'webp'])->max(2 * 1024)],
        };
    }
}
