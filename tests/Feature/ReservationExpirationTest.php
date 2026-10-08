<?php

namespace Tests\Feature;

use App\Models\Espace;
use App\Models\Reservation;
use App\Models\User;
use App\Services\PaiementService;
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

    public function test_reservation_avec_paiement_en_cours_recent_n_expire_pas(): void
    {
        config(['bookspace.reservation_expiration_minutes' => 30, 'bookspace.paiement_expiration_minutes' => 60]);

        $resa = $this->reservationAvecPaiementEnCours(lanceIlYa: 10);

        $this->artisan('reservations:expirer')->assertSuccessful();

        $this->assertSame('en_attente', $resa->fresh()->statut);
    }

    /** Paiement lancé puis abandonné : le créneau ne reste pas bloqué indéfiniment. */
    public function test_reservation_avec_paiement_abandonne_expire(): void
    {
        config(['bookspace.reservation_expiration_minutes' => 30, 'bookspace.paiement_expiration_minutes' => 60]);

        $resa = $this->reservationAvecPaiementEnCours(lanceIlYa: 90);

        $this->artisan('reservations:expirer')->assertSuccessful();

        $this->assertSame('annulee', $resa->fresh()->statut);
        $this->assertSame('echoue', $resa->paiements()->first()->statut);
    }

    /** Webhook de succès arrivé après l'expiration : pas de réactivation, litige pour remboursement. */
    public function test_paiement_recu_apres_expiration_ouvre_un_litige(): void
    {
        config(['bookspace.reservation_expiration_minutes' => 30, 'bookspace.paiement_expiration_minutes' => 60]);

        $resa = $this->reservationAvecPaiementEnCours(lanceIlYa: 90);
        $this->artisan('reservations:expirer')->assertSuccessful();

        app(PaiementService::class)->confirmerCallback('OP-TARD', true);

        $this->assertSame('annulee', $resa->fresh()->statut);
        $this->assertSame('valide', $resa->paiements()->first()->statut);
        $this->assertDatabaseHas('litiges', ['reservation_id' => $resa->id, 'ouvert_par' => $resa->client_id, 'statut' => 'ouvert']);
    }

    private function reservationAvecPaiementEnCours(int $lanceIlYa): Reservation
    {
        $espace = Espace::factory()->create();
        $resa = Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'en_attente',
            'created_at' => now()->subHours(3),
        ]);
        $paiement = $resa->paiements()->create([
            'montant' => $resa->montant_acompte,
            'mode' => 'orange_money',
            'telephone' => '+22670000000',
            'reference_operateur' => 'OP-TARD',
            'statut' => 'en_cours',
        ]);
        $paiement->forceFill(['created_at' => now()->subMinutes($lanceIlYa)])->save();

        return $resa;
    }
}
