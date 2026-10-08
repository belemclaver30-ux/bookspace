<?php

namespace Tests\Feature;

use App\Models\Espace;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GerantReservationActionsTest extends TestCase
{
    use RefreshDatabase;

    private function reservationFuture(Espace $espace, array $attrs = []): Reservation
    {
        $debut = CarbonImmutable::now()->next('Monday')->setTime(10, 0);

        return Reservation::factory()->for($espace)->create(array_merge([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'confirmee',
            'date_debut' => $debut,
            'date_fin' => $debut->addHours(2),
            'duree_heures' => 2,
        ], $attrs));
    }

    public function test_le_gerant_reporte_une_reservation_vers_un_creneau_libre(): void
    {
        $espace = Espace::factory()->create();
        $resa = $this->reservationFuture($espace);
        $nouveau = CarbonImmutable::now()->next('Tuesday')->setTime(14, 0);

        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/reservations/{$resa->id}/reporter", [
                'date_debut' => $nouveau->toIso8601String(),
            ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'confirmee');

        $resa->refresh();
        $this->assertSame($nouveau->format('Y-m-d H:i'), $resa->date_debut->format('Y-m-d H:i'));
        $this->assertDatabaseHas('audit_events', ['sujet_type' => Reservation::class, 'action' => 'reportee']);
    }

    public function test_reporter_vers_un_creneau_occupe_est_refuse(): void
    {
        $espace = Espace::factory()->create();
        $a = $this->reservationFuture($espace);
        $bDebut = CarbonImmutable::now()->next('Wednesday')->setTime(9, 0);
        $b = $this->reservationFuture($espace, ['date_debut' => $bDebut, 'date_fin' => $bDebut->addHours(2)]);

        // Reporter A sur le créneau de B -> conflit
        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/reservations/{$a->id}/reporter", [
                'date_debut' => $bDebut->toIso8601String(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('date_debut');
    }

    public function test_reporter_avec_nouvelle_duree_recalcule_le_prix(): void
    {
        $espace = Espace::factory()->create(['tarif_horaire' => 5000]);
        $resa = $this->reservationFuture($espace); // 2 h -> 10 000
        $nouveau = CarbonImmutable::now()->next('Thursday')->setTime(15, 0);

        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/reservations/{$resa->id}/reporter", [
                'date_debut' => $nouveau->toIso8601String(),
                'duree_heures' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('data.prix_total', 15000)
            ->assertJsonPath('data.montant_acompte', 4500);
    }

    public function test_un_autre_gerant_ne_peut_pas_reporter(): void
    {
        $espace = Espace::factory()->create();
        $resa = $this->reservationFuture($espace);
        $autre = \App\Models\Gerant::factory()->valide()->create();

        $this->actingAs($autre->user)
            ->postJson("/api/gerant/reservations/{$resa->id}/reporter", [
                'date_debut' => CarbonImmutable::now()->addWeek()->toIso8601String(),
            ])
            ->assertForbidden();
    }

    public function test_le_gerant_annule_une_reservation_avec_motif(): void
    {
        $espace = Espace::factory()->create();
        $resa = $this->reservationFuture($espace);

        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/reservations/{$resa->id}/annuler", ['motif' => 'Terrain indisponible (travaux)'])
            ->assertOk()
            ->assertJsonPath('data.statut', 'annulee');

        $this->assertSame('Terrain indisponible (travaux)', $resa->fresh()->motif_annulation);
    }

    public function test_annuler_exige_un_motif(): void
    {
        $espace = Espace::factory()->create();
        $resa = $this->reservationFuture($espace);

        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/reservations/{$resa->id}/annuler", [])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('motif');
    }

    public function test_le_gerant_cree_une_reservation_manuelle_confirmee(): void
    {
        $espace = Espace::factory()->create(['tarif_horaire' => 5000]);
        $debut = CarbonImmutable::now()->next('Monday')->setTime(10, 0);

        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/espaces/{$espace->id}/reservations", [
                'date_debut' => $debut->toIso8601String(),
                'duree_heures' => 2,
                'client_nom' => 'Amadou Traoré',
                'client_telephone' => '+22670000099',
            ])
            ->assertCreated()
            ->assertJsonPath('data.statut', 'confirmee')
            ->assertJsonPath('data.prix_total', 10000)
            ->assertJsonPath('data.montant_commission', 0)
            ->assertJsonPath('data.client.name', 'Amadou Traoré')
            ->assertJsonPath('data.client.telephone', '+22670000099')
            ->assertJsonPath('data.origine', 'gerant');

        $this->assertDatabaseHas('reservations', [
            'espace_id' => $espace->id,
            'client_id' => null,
            'client_nom' => 'Amadou Traoré',
            'statut' => 'confirmee',
            'origine' => 'gerant',
        ]);
        $this->assertDatabaseHas('audit_events', ['sujet_type' => Reservation::class, 'action' => 'creee_manuellement']);
    }

    public function test_reservation_manuelle_sur_creneau_occupe_est_refusee(): void
    {
        $espace = Espace::factory()->create();
        $existante = $this->reservationFuture($espace);

        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/espaces/{$espace->id}/reservations", [
                'date_debut' => $existante->date_debut->toIso8601String(),
                'duree_heures' => 2,
                'client_nom' => 'Client conflit',
                'client_telephone' => '+22670000098',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('date_debut');
    }

    public function test_un_autre_gerant_ne_peut_pas_creer_de_reservation_manuelle(): void
    {
        $espace = Espace::factory()->create();
        $autre = \App\Models\Gerant::factory()->valide()->create();

        $this->actingAs($autre->user)
            ->postJson("/api/gerant/espaces/{$espace->id}/reservations", [
                'date_debut' => CarbonImmutable::now()->addWeek()->toIso8601String(),
                'duree_heures' => 2,
                'client_nom' => 'Client',
                'client_telephone' => '+22670000097',
            ])
            ->assertForbidden();
    }

    public function test_reservation_manuelle_exige_nom_et_telephone(): void
    {
        $espace = Espace::factory()->create();

        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/espaces/{$espace->id}/reservations", [
                'date_debut' => CarbonImmutable::now()->addWeek()->toIso8601String(),
                'duree_heures' => 2,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('client_nom')
            ->assertJsonValidationErrorFor('client_telephone');
    }

    public function test_une_reservation_passee_n_est_ni_reportable_ni_annulable(): void
    {
        $espace = Espace::factory()->create();
        $passee = Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'terminee',
            'date_debut' => now()->subDays(2),
            'date_fin' => now()->subDays(2)->addHours(2),
        ]);

        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/reservations/{$passee->id}/reporter", [
                'date_debut' => now()->addWeek()->toIso8601String(),
            ])
            ->assertStatus(422);
    }
}
