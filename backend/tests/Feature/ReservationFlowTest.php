<?php

namespace Tests\Feature;

use App\Models\Espace;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function creneauProchain(): string
    {
        // Prochain lundi 10:00 — toujours dans les horaires d'ouverture (08–20).
        return CarbonImmutable::now()->next('Monday')->setTime(10, 0)->toIso8601String();
    }

    public function test_recherche_filtre_par_type_et_ville(): void
    {
        Espace::factory()->create(['ville' => 'Ouagadougou', 'nom' => 'Studio A']);
        Espace::factory()->terrain()->create(['ville' => 'Bobo-Dioulasso', 'nom' => 'Terrain B']);

        $this->getJson('/api/espaces?type=salle_repetition')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nom', 'Studio A');

        $this->getJson('/api/espaces?ville=Bobo')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nom', 'Terrain B');
    }

    public function test_espace_non_valide_invisible(): void
    {
        $espace = Espace::factory()->enAttente()->create();

        $this->getJson('/api/espaces')->assertJsonCount(0, 'data');
        $this->getJson("/api/espaces/{$espace->id}")->assertNotFound();
    }

    public function test_parcours_reservation_paiement_confirmation(): void
    {
        $client = User::factory()->client()->create();
        $espace = Espace::factory()->create(['tarif_horaire' => 5000]);

        $resa = $this->actingAs($client)->postJson('/api/reservations', [
            'espace_id' => $espace->id,
            'date_debut' => $this->creneauProchain(),
            'duree_heures' => 2,
        ])->assertCreated()
            ->assertJsonPath('data.statut', 'en_attente')
            ->assertJsonPath('data.prix_total', 10000)
            ->assertJsonPath('data.montant_acompte', 3000)     // 30 %
            ->assertJsonPath('data.montant_commission', 300)   // 10 % de l'acompte
            ->json('data.id');

        // Paiement Yenga (driver fake → validé immédiatement)
        $this->actingAs($client)->postJson("/api/reservations/{$resa}/paiement")
            ->assertOk()->assertJsonPath('data.statut', 'confirmee');

        $this->assertDatabaseHas('paiements', ['reservation_id' => $resa, 'statut' => 'valide']);
        $this->assertDatabaseHas('audit_events', ['sujet_type' => Reservation::class, 'action' => 'confirmee']);

        // Le gérant doit être averti par SMS (pas seulement en push) : il peut
        // ne pas être connecté à l'app au moment de la réservation.
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $espace->gerant->user_id,
            'type' => 'nouvelle_reservation',
            'canal' => 'sms',
        ]);
    }

    public function test_impossible_de_reserver_deux_fois_le_meme_creneau(): void
    {
        $client = User::factory()->client()->create();
        $autre = User::factory()->client()->create();
        $espace = Espace::factory()->create();
        $creneau = $this->creneauProchain();

        $this->actingAs($client)->postJson('/api/reservations', [
            'espace_id' => $espace->id, 'date_debut' => $creneau, 'duree_heures' => 2,
        ])->assertCreated();

        $this->actingAs($autre)->postJson('/api/reservations', [
            'espace_id' => $espace->id, 'date_debut' => $creneau, 'duree_heures' => 1,
        ])->assertStatus(422)->assertJsonValidationErrorFor('date_debut');

        $this->assertSame(1, Reservation::count());
    }

    public function test_creneau_hors_horaires_refuse(): void
    {
        $client = User::factory()->client()->create();
        $espace = Espace::factory()->create();

        $this->actingAs($client)->postJson('/api/reservations', [
            'espace_id' => $espace->id,
            'date_debut' => CarbonImmutable::now()->next('Monday')->setTime(6, 0)->toIso8601String(),
            'duree_heures' => 1,
        ])->assertStatus(422);
    }

    public function test_creneaux_tiennent_compte_de_la_duree_demandee(): void
    {
        $espace = Espace::factory()->create(); // ouvert 08:00–20:00
        $lundi = CarbonImmutable::now()->next('Monday')->toDateString();

        $pour1h = $this->getJson("/api/espaces/{$espace->id}/creneaux?date={$lundi}&duree=1")->assertOk();
        $pour3h = $this->getJson("/api/espaces/{$espace->id}/creneaux?date={$lundi}&duree=3")->assertOk();

        $slot1h = collect($pour1h->json('creneaux'))->firstWhere('heure', '18:00');
        $slot3h = collect($pour3h->json('creneaux'))->firstWhere('heure', '18:00');

        $this->assertTrue($slot1h['disponible']);   // 18:00–19:00 : dans les horaires
        $this->assertFalse($slot3h['disponible']);  // 18:00–21:00 : dépasse la fermeture
    }

    public function test_le_client_peut_annuler_puis_le_creneau_se_libere(): void
    {
        $client = User::factory()->client()->create();
        $espace = Espace::factory()->create();
        $creneau = $this->creneauProchain();

        $id = $this->actingAs($client)->postJson('/api/reservations', [
            'espace_id' => $espace->id, 'date_debut' => $creneau, 'duree_heures' => 1,
        ])->json('data.id');

        $this->actingAs($client)->postJson("/api/reservations/{$id}/annuler", ['motif' => 'Empêchement'])
            ->assertOk()->assertJsonPath('data.statut', 'annulee');

        // Le créneau est de nouveau réservable
        $this->actingAs(User::factory()->client()->create())->postJson('/api/reservations', [
            'espace_id' => $espace->id, 'date_debut' => $creneau, 'duree_heures' => 1,
        ])->assertCreated();
    }

    public function test_evaluation_possible_seulement_apres_cloture(): void
    {
        $client = User::factory()->client()->create();
        $espace = Espace::factory()->create();

        $resa = Reservation::factory()->for($client, 'client')->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'confirmee',
        ]);

        $this->actingAs($client)->postJson("/api/reservations/{$resa->id}/evaluation", ['note' => 5])
            ->assertStatus(422);

        $resa->update(['statut' => 'terminee']);

        $this->actingAs($client)->postJson("/api/reservations/{$resa->id}/evaluation", [
            'note' => 4, 'commentaire' => 'Très bien',
        ])->assertCreated();

        $this->assertEqualsWithDelta(4.0, (float) $espace->fresh()->note_moyenne, 0.01);
        $this->assertSame(1, $espace->fresh()->nb_avis);
    }

    public function test_cloturer_reservations_passe_les_creneaux_ecoules_en_terminee(): void
    {
        $espace = Espace::factory()->create();
        $resa = Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'confirmee',
            'date_debut' => now()->subHours(3),
            'date_fin' => now()->subHours(1),
        ]);

        $this->artisan('reservations:cloturer')->assertSuccessful();

        $this->assertSame('terminee', $resa->fresh()->statut);
    }
}
