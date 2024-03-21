<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'              => $this->faker->name,
            'Registration'      => $this->faker->unique()->numberBetween(100000, 999999),
            'email'             => $this->faker->unique()->safeEmail,
            'password'          => bcrypt('password'), // You can generate a hashed password here
            'superadm'          => $this->faker->boolean,
            'admin'             => $this->faker->boolean,
            'management'        => $this->faker->boolean,
            'operator'          => $this->faker->boolean,
            'user'              => $this->faker->boolean,
            'contract'          => $this->faker->boolean,
            'first_pass'        => $this->faker->boolean,
            'bypassprod'        => $this->faker->boolean,
            'engineer'          => $this->faker->boolean,
            'email_verified_at' => now(),
            'remember_token'    => Str::random(10),
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
