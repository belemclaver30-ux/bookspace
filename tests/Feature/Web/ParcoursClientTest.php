<?php

namespace Tests\Feature\Web;

use App\Livewire\Client\Accueil;
use App\Livewire\Client\Paiement;
use App\Livewire\Client\ReservationDetail;
use App\Livewire\Client\Reserver;
use App\Livewire\Notifications;
use App\Models\Espace;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ParcoursClientTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->client()->create();
        $this->actingAs($this->client);
    }

    public function test_accueil_liste_les_espaces_publies_et_filtre(): void
    {
        Espace::factory()->create(['nom' => 'Studio Harmonie']);
        Espace::factory()->terrain()->create(['nom' => 'Terrain Elite']);
        Espace::factory()->enAttente()->create(['nom' => 'Studio Caché']);

        $this->get('/app')->assertOk()
            ->assertSee('Studio Harmonie')->assertSee('Terrain Elite')->assertDontSee('Studio Caché');

        Livewire::test(Accueil::class)
            ->call('filtrer', 'terrain_foot')
            ->assertSee('Terrain Elite')->assertDontSee('Studio Harmonie')
            ->call('filtrer', 'tous')
            ->set('q', 'Harmonie')
            ->assertSee('Studio Harmonie')->assertDontSee('Terrain Elite');
    }

    public function test_tri_par_proximite(): void
    {
        Espace::factory()->create(['nom' => 'Studio Loin', 'latitude' => 11.18, 'longitude' => -4.29]);
        Espace::factory()->create(['nom' => 'Studio Proche', 'latitude' => 12.37, 'longitude' => -1.52]);

        Livewire::test(Accueil::class)
            ->call('activerProximite', 12.3714, -1.5197)
            ->assertSet('proche', true)
            ->assertSeeInOrder(['Studio Proche', 'Studio Loin']);
    }

    public function test_fiche_espace_visible_seulement_si_publie(): void
    {
        $publie = Espace::factory()->create(['nom' => 'Studio Harmonie']);
        $cache = Espace::factory()->enAttente()->create();

        $this->get("/espaces/{$publie->id}")->assertOk()->assertSee('Studio Harmonie')->assertSee('Réserver maintenant');
        $this->get("/espaces/{$cache->id}")->assertNotFound();
        $this->get("/espaces/{$cache->id}/reserver")->assertNotFound();
    }

    public function test_parcours_reservation_paiement_confirmation(): void
    {
        $espace = Espace::factory()->create(['tarif_horaire' => 5000]);

        Livewire::test(Reserver::class, ['espace' => $espace])
            ->assertSee('10:00')
            ->call('choisirDuree', 2)
            ->call('choisirCreneau', '10:00')
            ->assertSet('creneau', '10:00')
            ->call('reserver')
            ->assertHasNoErrors();

        $reservation = Reservation::firstOrFail();
        $this->assertSame('en_attente', $reservation->statut);
        $this->assertSame($this->client->id, $reservation->client_id);
        $this->assertEquals(10000, $reservation->prix_total);
        $this->assertEquals(3000, $reservation->montant_acompte); // 30 %
        $this->assertTrue($reservation->date_debut->equalTo(CarbonImmutable::tomorrow()->setTime(10, 0)));

        // Paiement (driver fake → validé immédiatement).
        Livewire::test(Paiement::class, ['reservation' => $reservation])
            ->assertSee('3 000 FCFA')
            ->call('payer')
            ->assertRedirect("/reservations/{$reservation->id}/confirmation");

        $this->assertSame('confirmee', $reservation->fresh()->statut);
        $this->get("/reservations/{$reservation->id}/confirmation")->assertOk()->assertSee('Réservation confirmée');
        $this->get("/reservations/{$reservation->id}/paiement")->assertRedirect("/reservations/{$reservation->id}/confirmation");
    }

    public function test_un_creneau_deja_pris_ne_peut_pas_etre_reserve(): void
    {
        $espace = Espace::factory()->create();
        $debut = CarbonImmutable::tomorrow()->setTime(10, 0);
        Reservation::factory()->create([
            'espace_id' => $espace->id, 'statut' => 'confirmee',
            'date_debut' => $debut, 'date_fin' => $debut->addHours(2),
        ]);

        Livewire::test(Reserver::class, ['espace' => $espace])
            ->call('choisirCreneau', '10:00')
            ->assertSet('creneau', null)
            ->call('reserver')
            ->assertHasErrors('creneau');

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_un_client_ne_voit_pas_la_reservation_d_un_autre(): void
    {
        $autre = Reservation::factory()->create();

        $this->get("/reservations/{$autre->id}")->assertForbidden();
        $this->get("/reservations/{$autre->id}/paiement")->assertForbidden();
        $this->get("/reservations/{$autre->id}/confirmation")->assertForbidden();
    }

    public function test_annulation_directe_d_une_reservation_non_payee(): void
    {
        $reservation = Reservation::factory()->create(['client_id' => $this->client->id]);

        $this->get('/reservations')->assertOk()->assertSee($reservation->reference);

        Livewire::test(ReservationDetail::class, ['reservation' => $reservation])
            ->call('annuler')
            ->assertHasNoErrors();

        $this->assertSame('annulee', $reservation->fresh()->statut);
    }

    public function test_reservation_confirmee_passe_par_une_demande(): void
    {
        $reservation = Reservation::factory()->create(['client_id' => $this->client->id, 'statut' => 'confirmee']);

        $composant = Livewire::test(ReservationDetail::class, ['reservation' => $reservation])
            ->call('annuler')
            ->assertHasErrors('reservation');

        $this->assertSame('confirmee', $reservation->fresh()->statut);

        $composant->call('ouvrir', 'report')
            ->set('date', $reservation->date_debut->addDay()->format('Y-m-d\TH:i'))
            ->set('motif', 'Empêchement')
            ->call('envoyerDemande')
            ->assertHasNoErrors()
            ->assertSee('Demande de report envoyée');

        $this->assertDatabaseHas('demandes_modification', [
            'reservation_id' => $reservation->id, 'type' => 'report', 'statut' => 'en_attente', 'motif' => 'Empêchement',
        ]);

        $composant->call('retirerDemande');
        $this->assertDatabaseHas('demandes_modification', ['reservation_id' => $reservation->id, 'statut' => 'retiree']);
    }

    public function test_evaluation_apres_une_reservation_terminee(): void
    {
        $reservation = Reservation::factory()->create(['client_id' => $this->client->id, 'statut' => 'terminee']);

        Livewire::test(ReservationDetail::class, ['reservation' => $reservation])
            ->set('note', 4)
            ->set('commentaire', 'Très bon studio')
            ->call('evaluer')
            ->assertHasNoErrors()
            ->assertSee('Merci pour votre avis')
            ->call('evaluer')
            ->assertHasErrors('reservation');

        $this->assertDatabaseCount('evaluations', 1);
        $this->assertEquals(4, $reservation->espace->fresh()->note_moyenne);
    }

    public function test_notifications_marquees_lues(): void
    {
        $this->client->notifications()->create([
            'canal' => 'push', 'type' => 'test', 'titre' => 'Réservation enregistrée', 'contenu' => 'Bienvenue',
        ]);

        Livewire::test(Notifications::class)
            ->assertSee('Réservation enregistrée')
            ->assertSee('Tout marquer comme lu')
            ->call('toutMarquerLu')
            ->assertDontSee('Tout marquer comme lu');

        $this->assertSame(0, $this->client->notifications()->whereNull('lu_at')->count());
    }
}
