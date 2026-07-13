<?php

namespace Database\Factories;

use App\Models\TemporaryAvatarUpload;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemporaryAvatarUpload>
 */
class TemporaryAvatarUploadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'disk' => 'public',
            'path' => 'temporary-avatars/'.fake()->uuid().'.jpg',
            'original_name' => 'avatar.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024 * 100,
            'expires_at' => now()->addDay(),
        ];
    }

    /**
     * Indicate that the upload is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subSecond(),
        ]);
    }
}
