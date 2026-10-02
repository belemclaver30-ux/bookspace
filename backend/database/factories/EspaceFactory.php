<?php

namespace Database\Factories;

use App\Models\Gerant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Espace> */
class EspaceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gerant_id' => Gerant::factory()->valide(),
            'type' => 'salle_repetition',
            'nom' => 'Studio ' . fake()->word(),
            'description' => fake()->sentence(),
            'adresse' => fake()->streetAddress(),
            'ville' => 'Ouagadougou',
            'tarif_horaire' => 5000,
            'statut' => 'valide',
            'equipements' => ['WiFi', 'Climatisation'],
            'capacite' => 8,
            'isolation_phonique' => true,
        ];
    }

    public function terrain(): static
    {
        return $this->state([
            'type' => 'terrain_foot',
            'nom' => 'Terrain ' . fake()->word(),
            'tarif_horaire' => 12000,
            'type_pelouse' => 'Synthétique',
            'eclairage' => true,
            'dimensions' => '90m x 50m',
            'capacite' => null,
            'isolation_phonique' => false,
        ]);
    }

    public function enAttente(): static
    {
        return $this->state(['statut' => 'en_attente']);
    }

    /** Ouvert tous les jours 08:00–20:00. */
    public function configure(): static
    {
        return $this->afterCreating(function ($espace) {
            for ($jour = 0; $jour <= 6; $jour++) {
                $espace->disponibilites()->create([
                    'jour_semaine' => $jour,
                    'heure_ouverture' => '08:00',
                    'heure_fermeture' => '20:00',
                ]);
            }
        });
    }
}
