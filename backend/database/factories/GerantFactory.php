<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Gerant> */
class GerantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->gerant(),
            'raison_sociale' => fake()->company(),
            'telephone_pro' => '+226' . fake()->numerify('########'),
            'ville' => 'Ouagadougou',
            'statut_validation' => 'en_attente',
        ];
    }

    public function valide(): static
    {
        return $this->state(['statut_validation' => 'valide', 'valide_at' => now()]);
    }
}
