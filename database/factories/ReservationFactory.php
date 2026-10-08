<?php

namespace Database\Factories;

use App\Models\Espace;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\Reservation> */
class ReservationFactory extends Factory
{
    public function definition(): array
    {
        $debut = CarbonImmutable::now()->next('Monday')->setTime(10, 0);
        $tarif = 5000;
        $duree = 2;
        $prix = $tarif * $duree;
        $acompte = $prix * 0.30;
        $commission = $acompte * 0.10;

        return [
            'reference' => 'BK-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)),
            'client_id' => User::factory()->client(),
            'espace_id' => Espace::factory(),
            'gerant_id' => fn (array $attrs) => Espace::find($attrs['espace_id'])->gerant_id,
            'date_debut' => $debut,
            'date_fin' => $debut->addHours($duree),
            'duree_heures' => $duree,
            'prix_total' => $prix,
            'taux_acompte' => 30,
            'montant_acompte' => $acompte,
            'taux_commission' => 10,
            'montant_commission' => $commission,
            'montant_reverse_gerant' => $acompte - $commission,
            'statut' => 'en_attente',
        ];
    }
}
