<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UsersGroup>
 */
class UsersGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Str::random(6),
            'group_name' => $this->faker->name(),
            'users' => null,
            'company_id' => 'bc2d3bf2-4eb0-47db-8f1e-6d2a76b94607'
        ];
    }
}
