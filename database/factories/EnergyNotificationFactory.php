<?php

namespace Database\Factories;

use App\Models\EnergyNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EnergyNotification>
 */
class EnergyNotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EnergyNotification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['electricity', 'gas'];
        $severities = ['info', 'warning', 'critical'];
        $statuses = ['unread', 'read'];
        $type = $this->faker->randomElement($types);

        $messages = [
            'electricity' => [
                'Je elektriciteitsverbruik is hoger dan verwacht',
                'Elektriciteitsverbruik binnen de normale grenzen',
                'Kritiek elektriciteitsverbruik gedetecteerd',
                'Je hebt je elektriciteitsdoel voor deze maand bereikt',
            ],
            'gas' => [
                'Je gasverbruik is hoger dan verwacht',
                'Gasverbruik binnen de normale grenzen',
                'Kritiek gasverbruik gedetecteerd',
                'Je hebt je gasdoel voor deze maand bereikt',
            ],
        ];

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'severity' => $this->faker->randomElement($severities),
            'message' => $this->faker->randomElement($messages[$type]),
            'status' => $this->faker->randomElement($statuses),
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create an unread notification
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unread',
        ]);
    }

    /**
     * Create a read notification
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'read',
        ]);
    }

    /**
     * Create an electricity notification
     */
    public function electricity(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'electricity',
            'message' => 'Je elektriciteitsverbruik is hoger dan verwacht',
        ]);
    }

    /**
     * Create a gas notification
     */
    public function gas(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'gas',
            'message' => 'Je gasverbruik is hoger dan verwacht',
        ]);
    }

    /**
     * Create a notification with specific severity
     */
    public function severity(string $severity): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => $severity,
        ]);
    }

    /**
     * Create an expired notification
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Create a notification that expires in the future
     */
    public function notExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
        ]);
    }
}
