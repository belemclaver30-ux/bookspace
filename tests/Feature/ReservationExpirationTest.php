<?php

namespace Tests\Feature;

use App\Models\Espace;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_impayee_hors_delai_est_expiree_et_le_creneau_libere(): void
    {
        config(['bookspace.reservation_expiration_minutes' => 30]);

        $espace = Espace::factory()->create();
        $creneau = CarbonImmutable::now()->next('Monday')->setTime(10, 0);

        $vieille = Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'en_attente',
            'date_debut' => $creneau,
            'date_fin' => $creneau->addHours(2),
            'created_at' => now()->subMinutes(45),
        ]);
        $recente = Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'en_attente',
            'date_debut' => $creneau->addHours(3),
            'date_fin' => $creneau->addHours(4),
            'created_at' => now()->subMinutes(5),
        ]);

        $this->artisan('reservations:expirer')->assertSuccessful();

        $this->assertSame('annulee', $vieille->fresh()->statut);
        $this->assertSame('en_attente', $recente->fresh()->statut);

        // Le créneau libéré redevient réservable.
        $this->actingAs(User::factory()->client()->create())->postJson('/api/reservations', [
            'espace_id' => $espace->id,
            'date_debut' => $creneau->toIso8601String(),
            'duree_heures' => 2,
        ])->assertCreated();
    }

    public function test_reservation_avec_paiement_en_cours_n_expire_pas(): void
    {
        config(['bookspace.reservation_expiration_minutes' => 30]);

        $espace = Espace::factory()->create();
        $resa = Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'en_attente',
            'created_at' => now()->subHours(3),
        ]);
        $resa->paiements()->create([
            'montant' => $resa->montant_acompte,
            'mode' => 'orange_money',
            'telephone' => '+22670000000',
            'statut' => 'en_cours',
        ]);

        $this->artisan('reservations:expirer')->assertSuccessful();

        $this->assertSame('en_attente', $resa->fresh()->statut);
    }
}
