<?php

namespace Tests\Feature;

use App\Models\Espace;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandeModificationTest extends TestCase
{
    use RefreshDatabase;

    private function reservationConfirmee(): array
    {
        $client = User::factory()->client()->create();
        $espace = Espace::factory()->create();
        $debut = CarbonImmutable::now()->next('Monday')->setTime(10, 0);
        $resa = Reservation::factory()->for($client, 'client')->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'confirmee',
            'date_debut' => $debut,
            'date_fin' => $debut->addHours(2),
            'duree_heures' => 2,
        ]);

        return [$client, $espace, $resa];
    }

    public function test_le_client_demande_l_annulation_d_une_reservation_confirmee(): void
    {
        [$client, $espace, $resa] = $this->reservationConfirmee();

        $this->actingAs($client)
            ->postJson("/api/reservations/{$resa->id}/demandes", [
                'type' => 'annulation',
                'motif' => 'Match reporté par la ligue',
            ])
            ->assertOk()
            ->assertJsonPath('data.demande.type', 'annulation')
            ->assertJsonPath('data.demande.statut', 'en_attente')
            ->assertJsonPath('data.statut', 'confirmee'); // pas encore annulée

        $this->assertDatabaseHas('demandes_modification', [
            'reservation_id' => $resa->id, 'type' => 'annulation', 'statut' => 'en_attente',
        ]);
    }

    public function test_annuler_directement_une_reservation_confirmee_est_refuse(): void
    {
        [$client, , $resa] = $this->reservationConfirmee();

        $this->actingAs($client)
            ->postJson("/api/reservations/{$resa->id}/annuler", [])
            ->assertStatus(422);
    }

    public function test_une_seule_demande_en_attente_a_la_fois(): void
    {
        [$client, , $resa] = $this->reservationConfirmee();

        $this->actingAs($client)->postJson("/api/reservations/{$resa->id}/demandes", ['type' => 'annulation'])->assertOk();
        $this->actingAs($client)->postJson("/api/reservations/{$resa->id}/demandes", ['type' => 'report', 'date_debut' => CarbonImmutable::now()->addWeek()->toIso8601String()])
            ->assertStatus(422);
    }

    public function test_le_gerant_accepte_une_demande_d_annulation(): void
    {
        [$client, $espace, $resa] = $this->reservationConfirmee();

        $demandeId = $this->actingAs($client)
            ->postJson("/api/reservations/{$resa->id}/demandes", ['type' => 'annulation', 'motif' => 'Empêchement'])
            ->json('data.demande.id');

        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/demandes/{$demandeId}/accepter")
            ->assertOk()
            ->assertJsonPath('data.statut', 'acceptee');

        $this->assertSame('annulee', $resa->fresh()->statut);
        $this->assertStringContainsString('Empêchement', $resa->fresh()->motif_annulation);
    }

    public function test_le_gerant_accepte_une_demande_de_report_et_la_reservation_est_deplacee(): void
    {
        [$client, $espace, $resa] = $this->reservationConfirmee();
        $nouveau = CarbonImmutable::now()->next('Wednesday')->setTime(15, 0);

        $demandeId = $this->actingAs($client)
            ->postJson("/api/reservations/{$resa->id}/demandes", [
                'type' => 'report',
                'date_debut' => $nouveau->toIso8601String(),
            ])
            ->json('data.demande.id');

        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/demandes/{$demandeId}/accepter")
            ->assertOk();

        $this->assertSame($nouveau->format('Y-m-d H:i'), $resa->fresh()->date_debut->format('Y-m-d H:i'));
    }

    public function test_le_gerant_refuse_une_demande_avec_une_reponse(): void
    {
        [$client, $espace, $resa] = $this->reservationConfirmee();
        $demandeId = $this->actingAs($client)
            ->postJson("/api/reservations/{$resa->id}/demandes", ['type' => 'annulation'])
            ->json('data.demande.id');

        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/demandes/{$demandeId}/refuser", ['reponse' => 'Créneau déjà bloqué, acompte non remboursable.'])
            ->assertOk()
            ->assertJsonPath('data.statut', 'refusee')
            ->assertJsonPath('data.reponse_gerant', 'Créneau déjà bloqué, acompte non remboursable.');

        $this->assertSame('confirmee', $resa->fresh()->statut);
    }

    public function test_le_client_retire_sa_demande(): void
    {
        [$client, , $resa] = $this->reservationConfirmee();
        $demandeId = $this->actingAs($client)
            ->postJson("/api/reservations/{$resa->id}/demandes", ['type' => 'annulation'])
            ->json('data.demande.id');

        $this->actingAs($client)->deleteJson("/api/demandes/{$demandeId}")->assertOk();
        $this->assertDatabaseHas('demandes_modification', ['id' => $demandeId, 'statut' => 'retiree']);
    }

    public function test_le_gerant_liste_les_demandes_en_attente_de_ses_espaces(): void
    {
        [$client, $espace, $resa] = $this->reservationConfirmee();
        $this->actingAs($client)->postJson("/api/reservations/{$resa->id}/demandes", ['type' => 'annulation']);

        // Un autre gérant ne voit rien
        $autre = \App\Models\Gerant::factory()->valide()->create();
        $this->actingAs($autre->user)->getJson('/api/gerant/demandes')->assertOk()->assertJsonCount(0, 'data');

        $this->actingAs($espace->gerant->user)->getJson('/api/gerant/demandes')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_un_autre_gerant_ne_peut_pas_traiter_la_demande(): void
    {
        [$client, $espace, $resa] = $this->reservationConfirmee();
        $demandeId = $this->actingAs($client)
            ->postJson("/api/reservations/{$resa->id}/demandes", ['type' => 'annulation'])
            ->json('data.demande.id');

        $autre = \App\Models\Gerant::factory()->valide()->create();
        $this->actingAs($autre->user)->postJson("/api/gerant/demandes/{$demandeId}/accepter")->assertForbidden();
    }
}
