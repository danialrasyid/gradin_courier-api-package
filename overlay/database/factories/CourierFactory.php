<?php

namespace Database\Factories;

use App\Enums\VehicleType;
use App\Models\Courier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Courier>
 */
class CourierFactory extends Factory
{
    protected $model = Courier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '08'.fake()->unique()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'level' => fake()->numberBetween(Courier::MIN_LEVEL, Courier::MAX_LEVEL),
            'vehicle_type' => fake()->randomElement(VehicleType::values()),
            'vehicle_plate_number' => strtoupper(fake()->bothify('L #### ??')),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }

    public function level(int $level): static
    {
        return $this->state(fn () => ['level' => $level]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
