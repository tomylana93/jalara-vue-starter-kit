<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->profileRules($this->user()->id), [
            'temporary_avatar_upload_id' => ['nullable', 'uuid'],
        ]);
    }

    /**
     * Get the validated profile attributes with their guaranteed shape.
     *
     * @return array{name: string, email: string, phone: ?string}
     */
    public function profileAttributes(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'email' => $this->string('email')->toString(),
            'phone' => $this->filled('phone') ? $this->string('phone')->toString() : null,
        ];
    }

    /**
     * Get the validated temporary avatar upload ID.
     */
    public function temporaryAvatarUploadId(): ?string
    {
        return $this->validated()['temporary_avatar_upload_id'] ?? null;
    }
}
