<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Corte clásico',
                'Corte degradado',
                'Barba',
                'Cejas',
                'Combo completo',
                'Lavado y peinado',
            ]),
            'description' => fake()->sentence(8),
            'price' => fake()->randomFloat(2, 1500, 12000),
            'duration' => fake()->randomElement([15, 30, 45, 60]),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the service is deactivated.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
