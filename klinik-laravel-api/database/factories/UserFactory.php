<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
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
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // default password
            'is_admin' => $this->faker->boolean(10), // 10% chance of being true
            'is_doctor' => $this->faker->boolean(10),
            'is_pharmacist' => $this->faker->boolean(10),
            'is_laboratorist' => $this->faker->boolean(10),
            'is_nurse' => $this->faker->boolean(10),
            'avatar' => $this->faker->imageUrl(),
            'website' => $this->faker->url,
            'twitter' => $this->faker->url,
            'instagram' => $this->faker->url,
            'facebook' => $this->faker->url,
            'bio' => 'your bio here',
            'facebook_id' => $this->faker->uuid,
            'remember_token' => Str::random(10),
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
