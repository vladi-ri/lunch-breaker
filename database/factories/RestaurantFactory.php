<?php

namespace Database\Factories;

use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Restaurant>
 */
class RestaurantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'office_id'                => Office::factory(),
            'name'                     => fake()->company(),
            'source'                   => 'manual',
            'external_id'              => null,
            'address'                  => fake()->streetAddress().', '.fake()->city(),
            'latitude'                 => fake()->latitude(),
            'longitude'                => fake()->longitude(),
            'category'                 => 'restaurant',
            'walking_distance_meters'  => fake()->numberBetween(100, 1500),
            'walking_duration_seconds' => fake()->numberBetween(60, 1200),
            'distance_calculated_at'   => now(),
            'is_active'                => true,
            'menu_source_type'         => 'manual',
        ];
    }
}
