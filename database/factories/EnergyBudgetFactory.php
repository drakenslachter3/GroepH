<?php

namespace Database\Factories;

use App\Models\EnergyBudget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EnergyBudget>
 */
class EnergyBudgetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EnergyBudget::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gasM3 = $this->faker->numberBetween(1000, 2500);

        return [
            'user_id' => User::factory(),
            'year' => date('Y'),
            'electricity_target_kwh' => $this->faker->numberBetween(2000, 5000),
            'gas_target_m3' => $gasM3,
            'gas_target_euro' => round($gasM3 * 1.5), // Roughly 1.5 euro per m3
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a budget for the current year
     */
    public function currentYear(): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => date('Y'),
        ]);
    }

    /**
     * Create a budget for a specific year
     */
    public function forYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => $year,
        ]);
    }

    /**
     * Create a budget with specific electricity target
     */
    public function withElectricityTarget(int $kwh): static
    {
        return $this->state(fn (array $attributes) => [
            'electricity_target_kwh' => $kwh,
        ]);
    }

    /**
     * Create a budget with specific gas target in euros
     */
    public function withGasTargetEuro(int $euros): static
    {
        return $this->state(fn (array $attributes) => [
            'gas_target_euro' => $euros,
        ]);
    }
}
