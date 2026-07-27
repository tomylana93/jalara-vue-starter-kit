<?php

namespace Database\Factories;

use App\Enums\TemporaryUploadPurpose;
use App\Models\TemporaryUpload;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TemporaryUpload> */
class TemporaryUploadFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'purpose' => TemporaryUploadPurpose::Branding,
            'branding_field' => 'icon',
            'disk' => 'local',
            'path' => 'temporary-uploads/'.fake()->uuid().'.png',
            'original_name' => 'upload.png',
            'mime_type' => 'image/png',
            'size' => 1,
            'expires_at' => now()->addHour(),
        ];
    }
}
