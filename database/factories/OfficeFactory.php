<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Office>
 */
class OfficeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'             => User::factory(),
            'name'                => fake()->company().' Office',
            'address'             => fake()->streetAddress().', '.fake()->city(),
            'latitude'            => fake()->latitude(),
            'longitude'           => fake()->longitude(),
            'max_distance_meters' => 1500,
            'max_walking_minutes' => null,
            'distance_unit'       => 'meters',
            'geocoded_at'         => now(),
        ];
    }

    /**
     * Indicate that the office has not been geocoded yet.
     */
    public function ungeocoded(): static
    {
        return $this->state(fn (array $attributes) => [
            'latitude'    => null,
            'longitude'   => null,
            'geocoded_at' => null,
        ]);
    }
}
