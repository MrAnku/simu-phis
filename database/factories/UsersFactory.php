<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UsersFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => null,
            'user_name' => fake()->name(),
            'user_email' => fake()->unique()->safeEmail(),
            'user_company' => fake()->company(),
            'user_job_title' => fake()->jobTitle(),
            'whatsapp' => fake()->numerify('############'),
            'breach_scan_date' => fake()->dateTimeThisYear(),
            'company_id' => 'bc2d3bf2-4eb0-47db-8f1e-6d2a76b94607',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
