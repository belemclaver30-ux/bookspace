<?php

namespace Database\Seeders;

use App\Models\Espace;
use App\Models\EspaceDisponibilite;
use App\Models\EspacePhoto;
use App\Models\Gerant;
use App\Models\Parametre;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Paramètres plateforme (TDR §5.3) ────────────────────────────────
        Parametre::definir('commission_rate', '10', 'Commission plateforme (%) sur chaque réservation confirmée');
        Parametre::definir('deposit_rate', '30', "Acompte (%) exigé à la réservation");
        Parametre::definir('annulation_delai_heures', '24', "Délai minimum d'annulation sans frais (heures)");

        // ─── Administrateur ──────────────────────────────────────────────────
        User::updateOrCreate(
            ['telephone' => '+22600000000'],
            [
                'name' => 'Administrateur BookSpace',
                'email' => 'admin@bookspace.bf',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
        );

        // ─── Client de démonstration ────────────────────────────────────────
        $client = User::updateOrCreate(
            ['telephone' => '+22670000001'],
            [
                'name' => 'Konan Yao Marcel',
                'email' => 'marcel@exemple.com',
                'password' => Hash::make('password'),
                'role' => 'client',
            ],
        );

        // ─── Gérants + espaces (données du prototype src/App.tsx) ────────────
        $catalogue = [
            [
                'gerant' => ['name' => 'Studio Harmonie SARL', 'tel' => '+22670000010', 'raison' => 'Studio Harmonie'],
                'espace' => [
                    'type' => 'salle_repetition',
                    'nom' => 'Studio Harmonie',
                    'ville' => 'Ouagadougou, Ouaga 2000',
                    'adresse' => 'Rue 28.104, Ouaga 2000',
                    'tarif_horaire' => 5000,
                    'description' => "Studio de répétition moderne et entièrement équipé, idéal pour groupes et artistes indépendants. Espace insonorisé, ambiance professionnelle.",
                    'equipements' => ['Sonorisation', 'Climatisation', 'WiFi', 'Parking'],
                    'capacite' => 8,
                    'isolation_phonique' => true,
                    'note_moyenne' => 4.8,
                    'nb_avis' => 124,
                    'photo' => 'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=800&h=480&fit=crop&auto=format',
                ],
            ],
            [
                'gerant' => ['name' => 'Elite FC Complexe', 'tel' => '+22670000011', 'raison' => 'Elite FC'],
                'espace' => [
                    'type' => 'terrain_foot',
                    'nom' => 'Terrain Elite FC',
                    'ville' => 'Ouagadougou, Gounghin',
                    'adresse' => 'Avenue Bassawarga, Gounghin',
                    'tarif_horaire' => 12000,
                    'description' => "Terrain de football synthétique aux normes, éclairage nocturne, vestiaires et buvette sur place.",
                    'equipements' => ['Vestiaires', 'Éclairage', 'Buvette'],
                    'type_pelouse' => 'Synthétique',
                    'eclairage' => true,
                    'dimensions' => '90m x 50m',
                    'note_moyenne' => 4.6,
                    'nb_avis' => 89,
                    'photo' => 'https://images.unsplash.com/photo-1459865264687-595d652de67e?w=800&h=480&fit=crop&auto=format',
                ],
            ],
            [
                'gerant' => ['name' => 'Beat Lab Studio', 'tel' => '+22670000012', 'raison' => 'Beat Lab'],
                'espace' => [
                    'type' => 'salle_repetition',
                    'nom' => 'Studio Beat Lab',
                    'ville' => 'Ouagadougou, Koulouba',
                    'adresse' => 'Avenue de la Nation, Koulouba',
                    'tarif_horaire' => 7500,
                    'description' => "Studio d'enregistrement et de répétition avec cabine et console professionnelle.",
                    'equipements' => ['Console', 'Cabine', 'WiFi', 'Parking'],
                    'capacite' => 6,
                    'isolation_phonique' => true,
                    'note_moyenne' => 4.9,
                    'nb_avis' => 211,
                    'photo' => 'https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?w=800&h=480&fit=crop&auto=format',
                ],
            ],
            [
                'gerant' => ['name' => 'Champions Arena', 'tel' => '+22670000013', 'raison' => 'Champions'],
                'espace' => [
                    'type' => 'terrain_foot',
                    'nom' => 'Terrain Champions',
                    'ville' => 'Ouagadougou, Pissy',
                    'adresse' => 'Route de Bobo, Pissy',
                    'tarif_horaire' => 8000,
                    'description' => "Terrain gazon synthétique avec éclairage et vestiaires, idéal pour matchs amicaux et tournois.",
                    'equipements' => ['Gazon synthétique', 'Éclairage', 'Vestiaires'],
                    'type_pelouse' => 'Gazon synthétique',
                    'eclairage' => true,
                    'dimensions' => '100m x 60m',
                    'note_moyenne' => 4.4,
                    'nb_avis' => 56,
                    'photo' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=800&h=480&fit=crop&auto=format',
                ],
            ],
        ];

        foreach ($catalogue as $row) {
            $user = User::updateOrCreate(
                ['telephone' => $row['gerant']['tel']],
                [
                    'name' => $row['gerant']['name'],
                    'password' => Hash::make('password'),
                    'role' => 'gerant',
                ],
            );

            $gerant = Gerant::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'raison_sociale' => $row['gerant']['raison'],
                    'telephone_pro' => $row['gerant']['tel'],
                    'ville' => 'Ouagadougou',
                    'statut_validation' => 'valide',
                    'valide_at' => now(),
                ],
            );

            $data = $row['espace'];
            $photo = $data['photo'];
            unset($data['photo']);

            $espace = Espace::updateOrCreate(
                ['gerant_id' => $gerant->id, 'nom' => $data['nom']],
                array_merge($data, ['statut' => 'valide']),
            );

            EspacePhoto::updateOrCreate(
                ['espace_id' => $espace->id, 'ordre' => 0],
                ['chemin' => $photo],
            );

            // Ouvert tous les jours 08:00–20:00
            for ($jour = 0; $jour <= 6; $jour++) {
                EspaceDisponibilite::updateOrCreate(
                    ['espace_id' => $espace->id, 'jour_semaine' => $jour, 'heure_ouverture' => '08:00'],
                    ['heure_fermeture' => '20:00'],
                );
            }
        }
    }
}
