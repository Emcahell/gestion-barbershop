<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
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
            'barber_id' => User::factory()->barber(),
            'service_id' => Service::factory(),
            'date' => fake()->dateTimeBetween('+1 day', '+14 days')->format('Y-m-d'),
            'time' => fake()->randomElement(Appointment::slots()),
            'status' => AppointmentStatus::Scheduled,
        ];
    }

    /**
     * Indicate that the appointment already happened and was completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
            'status' => AppointmentStatus::Completed,
        ]);
    }

    /**
     * Indicate that the appointment was cancelled by the client.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Cancelled,
        ]);
    }
}
