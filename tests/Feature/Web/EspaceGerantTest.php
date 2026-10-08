<?php

namespace Tests\Feature\Web;

use App\Livewire\Gerant\Demandes;
use App\Livewire\Gerant\EspaceGestion;
use App\Livewire\Gerant\Espaces;
use App\Livewire\Gerant\Reservations;
use App\Models\DemandeModification;
use App\Models\Espace;
use App\Models\Gerant;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EspaceGerantTest extends TestCase
{
    use RefreshDatabase;

    private Gerant $gerant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gerant = Gerant::factory()->valide()->create();
        $this->actingAs($this->gerant->user);
    }

    public function test_les_pages_du_gerant_s_affichent(): void
    {
        $espace = Espace::factory()->create(['gerant_id' => $this->gerant->id, 'nom' => 'Studio Harmonie']);
        Reservation::factory()->create(['espace_id' => $espace->id, 'statut' => 'confirmee']);

        $this->get('/gerant')->assertOk()->assertSee('Tableau de bord')->assertSee("Taux d'occupation");
        $this->get('/gerant/espaces')->assertOk()->assertSee('Studio Harmonie');
        $this->get("/gerant/espaces/{$espace->id}")->assertOk()->assertSee("Horaires d'ouverture", false);
        $this->get('/gerant/reservations')->assertOk()->assertSee('Réservations reçues');
        $this->get('/gerant/demandes')->assertOk()->assertSee('Demandes des clients');
        $this->get('/profil')->assertOk()->assertSee('Compte Gérant');
        $this->get('/notifications')->assertOk();
    }

    public function test_creation_d_un_espace_soumis_a_validation(): void
    {
        Livewire::test(Espaces::class)
            ->call('basculerCreation')
            ->set('type', 'terrain_foot')
            ->set('nom', 'Terrain Elite')
            ->set('adresse', 'Avenue Bassawarga')
            ->set('tarif_horaire', '12000')
            ->set('equipements', 'Vestiaires, Éclairage, ')
            ->call('creer')
            ->assertHasNoErrors()
            ->assertSet('creation', false)
            ->assertSee('Terrain Elite');

        $espace = Espace::firstOrFail();
        $this->assertSame('en_attente', $espace->statut);
        $this->assertSame($this->gerant->id, $espace->gerant_id);
        $this->assertSame(['Vestiaires', 'Éclairage'], $espace->equipements);
    }

    public function test_un_gerant_non_valide_ne_peut_pas_creer_d_espace(): void
    {
        $enAttente = Gerant::factory()->create();

        Livewire::actingAs($enAttente->user)->test(Espaces::class)
            ->set('nom', 'Studio')->set('adresse', 'Rue 1')->set('tarif_horaire', '5000')
            ->call('creer')
            ->assertForbidden();

        $this->assertDatabaseCount('espaces', 0);
    }

    public function test_un_gerant_ne_gere_pas_l_espace_d_un_autre(): void
    {
        $autre = Espace::factory()->create();

        $this->get("/gerant/espaces/{$autre->id}")->assertForbidden();
    }

    public function test_horaires_et_plages_bloquees(): void
    {
        $espace = Espace::factory()->create(['gerant_id' => $this->gerant->id]);

        $composant = Livewire::test(EspaceGestion::class, ['espace' => $espace])
            ->assertSet('horaires.1.actif', true)
            ->set('horaires.0.actif', false)
            ->set('horaires.1.o', '09:00')
            ->set('horaires.1.f', '18:00')
            ->call('enregistrerHoraires')
            ->assertHasNoErrors();

        $this->assertSame(6, $espace->disponibilites()->count());
        $this->assertDatabaseHas('espace_disponibilites', ['espace_id' => $espace->id, 'jour_semaine' => 1, 'heure_ouverture' => '09:00']);

        // Fermeture avant ouverture : refusé.
        $composant->set('horaires.2.f', '07:00')->call('enregistrerHoraires')->assertHasErrors('horaires');

        $debut = CarbonImmutable::tomorrow()->setTime(8, 0);
        $composant
            ->set('blocageDebut', $debut->format('Y-m-d\TH:i'))
            ->set('blocageFin', $debut->addHours(4)->format('Y-m-d\TH:i'))
            ->set('blocageMotif', 'Travaux')
            ->call('bloquer')
            ->assertHasNoErrors()
            ->assertSee('Travaux');

        $blocage = $espace->blocages()->firstOrFail();
        $composant->call('debloquer', $blocage->id);
        $this->assertSame(0, $espace->blocages()->count());
    }

    public function test_localisation_enregistree(): void
    {
        $espace = Espace::factory()->create(['gerant_id' => $this->gerant->id]);

        Livewire::test(EspaceGestion::class, ['espace' => $espace])
            ->call('enregistrerLocalisation')
            ->assertHasErrors('localisation')
            ->set('latitude', 12.3714)
            ->set('longitude', -1.5197)
            ->set('adresse', 'Rue 28.104, Ouaga 2000')
            ->call('enregistrerLocalisation')
            ->assertHasNoErrors();

        $espace->refresh();
        $this->assertEquals(12.3714, $espace->latitude);
        $this->assertSame('Rue 28.104, Ouaga 2000', $espace->adresse);
    }

    public function test_ajout_et_suppression_de_photos(): void
    {
        Storage::fake('public');
        $espace = Espace::factory()->create(['gerant_id' => $this->gerant->id]);

        $composant = Livewire::test(EspaceGestion::class, ['espace' => $espace])
            ->set('nouvellesPhotos', [UploadedFile::fake()->image('a.jpg', 800, 600), UploadedFile::fake()->image('b.png', 400, 300)])
            ->assertHasNoErrors();

        $this->assertSame(2, $espace->photos()->count());
        $premiere = $espace->photos()->firstOrFail();
        Storage::disk('public')->assertExists($premiere->chemin);
        $this->assertStringEndsWith('.webp', $premiere->chemin);

        $composant->call('supprimerPhoto', $premiere->id);
        $this->assertSame(1, $espace->photos()->count());
        Storage::disk('public')->assertMissing($premiere->chemin);

        // Un fichier qui n'est pas une image est refusé.
        $composant->set('nouvellesPhotos', [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])
            ->assertHasErrors('nouvellesPhotos.0');
        $this->assertSame(1, $espace->photos()->count());
    }

    public function test_reservation_manuelle_report_et_annulation(): void
    {
        $espace = Espace::factory()->create(['gerant_id' => $this->gerant->id]);
        $debut = CarbonImmutable::tomorrow()->setTime(10, 0);

        $composant = Livewire::test(Reservations::class)
            ->call('basculerAjout')
            ->set('espaceId', (string) $espace->id)
            ->set('date', $debut->format('Y-m-d\TH:i'))
            ->set('duree', '2')
            ->set('clientNom', 'Client Comptoir')
            ->set('clientTelephone', '+22670112233')
            ->call('creerManuelle')
            ->assertHasNoErrors()
            ->assertSet('ajout', false)
            ->assertSee('Client Comptoir')
            ->assertSee('Ajoutée manuellement');

        $reservation = Reservation::firstOrFail();
        $this->assertSame('confirmee', $reservation->statut);
        $this->assertSame('gerant', $reservation->origine);

        // Report au lendemain, même heure.
        $composant->call('ouvrirReport', $reservation->id)
            ->assertSet('reportId', $reservation->id)
            ->set('reportDate', $debut->addDay()->format('Y-m-d\TH:i'))
            ->call('reporter')
            ->assertHasNoErrors()
            ->assertSet('reportId', null);

        $this->assertTrue($reservation->fresh()->date_debut->equalTo($debut->addDay()));

        // Annulation : motif obligatoire.
        $composant->call('annuler', $reservation->id, '   ');
        $this->assertSame('confirmee', $reservation->fresh()->statut);

        $composant->call('annuler', $reservation->id, 'Salle indisponible');
        $this->assertSame('annulee', $reservation->fresh()->statut);
        $this->assertSame('Salle indisponible', $reservation->fresh()->motif_annulation);
    }

    public function test_le_gerant_ne_touche_pas_aux_reservations_d_un_autre(): void
    {
        $autre = Reservation::factory()->create(['statut' => 'confirmee']);

        // Introuvable pour ce gérant (rendu en 404 par l'application).
        $this->assertThrows(
            fn () => Livewire::test(Reservations::class)->call('annuler', $autre->id, 'Motif'),
            ModelNotFoundException::class,
        );

        $this->assertSame('confirmee', $autre->fresh()->statut);
    }

    public function test_traitement_des_demandes_client(): void
    {
        $espace = Espace::factory()->create(['gerant_id' => $this->gerant->id]);
        $annulation = $this->demande($espace, 'annulation');
        $report = $this->demande($espace, 'report');
        $etrangere = $this->demande(Espace::factory()->create(), 'annulation');

        $composant = Livewire::test(Demandes::class)
            ->assertSee($annulation->reservation->reference)
            ->assertDontSee($etrangere->reservation->reference)
            ->call('accepter', $annulation->id)
            ->assertHasNoErrors()
            ->call('refuser', $report->id, 'Créneau déjà promis');

        $this->assertSame('acceptee', $annulation->fresh()->statut);
        $this->assertSame('annulee', $annulation->reservation->fresh()->statut);
        $this->assertSame('refusee', $report->fresh()->statut);
        $this->assertSame('Créneau déjà promis', $report->fresh()->reponse_gerant);

        $this->assertThrows(fn () => $composant->call('accepter', $etrangere->id), ModelNotFoundException::class);
        $this->assertSame('en_attente', $etrangere->fresh()->statut);
    }

    private function demande(Espace $espace, string $type): DemandeModification
    {
        static $decalage = 0;
        $debut = CarbonImmutable::tomorrow()->setTime(9 + 3 * $decalage++, 0);

        $reservation = Reservation::factory()->create([
            'espace_id' => $espace->id, 'statut' => 'confirmee',
            'date_debut' => $debut, 'date_fin' => $debut->addHours(2),
        ]);

        return DemandeModification::create([
            'reservation_id' => $reservation->id,
            'demandeur_id' => $reservation->client_id,
            'type' => $type,
            'statut' => 'en_attente',
            'nouvelle_date_debut' => $type === 'report' ? $debut->addDays(2) : null,
            'nouvelle_duree_heures' => $type === 'report' ? 2 : null,
        ]);
    }

    public function test_le_gerant_modifie_la_fiche_de_son_espace(): void
    {
        $espace = Espace::factory()->create([
            'gerant_id' => $this->gerant->id, 'type' => 'terrain_foot', 'nom' => 'Terrain A', 'tarif_horaire' => 10000,
        ]);

        Livewire::test(EspaceGestion::class, ['espace' => $espace])
            ->assertSet('nom', 'Terrain A')
            ->set('nom', '  Terrain Élite ')
            ->set('tarif_horaire', '15000')
            ->set('equipements', 'Vestiaires, Douches, Vestiaires')
            ->set('type_pelouse', 'Synthétique')
            ->set('eclairage', true)
            ->set('capacite', '30') // champ de salle : ignoré pour un terrain
            ->call('enregistrerFiche')
            ->assertHasNoErrors()
            ->assertSee('Fiche enregistrée');

        $espace->refresh();
        $this->assertSame('Terrain Élite', $espace->nom);
        $this->assertEquals(15000, $espace->tarif_horaire);
        $this->assertSame(['Vestiaires', 'Douches'], $espace->equipements);
        $this->assertSame('Synthétique', $espace->type_pelouse);
        $this->assertTrue($espace->eclairage);
        $this->assertNull($espace->capacite);
        $this->assertSame('terrain_foot', $espace->type);
        $this->assertDatabaseHas('audit_events', ['sujet_id' => $espace->id, 'action' => 'modifiee']);
    }

    public function test_la_fiche_est_validee(): void
    {
        $espace = Espace::factory()->create(['gerant_id' => $this->gerant->id]);

        Livewire::test(EspaceGestion::class, ['espace' => $espace])
            ->set('nom', '')
            ->set('tarif_horaire', '-5')
            ->call('enregistrerFiche')
            ->assertHasErrors(['nom', 'tarif_horaire']);
    }

    public function test_un_gerant_ne_modifie_pas_l_espace_d_un_autre(): void
    {
        $autre = Espace::factory()->create();

        Livewire::test(EspaceGestion::class, ['espace' => $autre])->assertForbidden();
    }
}
