<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'telephone' => '+226' . fake()->unique()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'client',
            'actif' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function client(): static
    {
        return $this->state(['role' => 'client']);
    }

    public function gerant(): static
    {
        return $this->state(['role' => 'gerant']);
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null]);
    }
}
