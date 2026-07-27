<?php

namespace Database\Factories;

use App\Models\SiteBranding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SiteBranding> */
class SiteBrandingFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
        ];
    }
}
