<?php

namespace Tests\Feature;

use App\Models\Espace;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AbonnementService;
use App\Services\PaiementService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AbonnementFlowTest extends TestCase
{
    use RefreshDatabase;

    private function creneauProchain(): CarbonImmutable
    {
        // Prochain lundi 10:00 — toujours dans les horaires d'ouverture (08–20).
        return CarbonImmutable::now()->next('Monday')->setTime(10, 0);
    }

    private function espaceAvecFormule(int $nombreSeances = 4, float $prix = 20000): array
    {
        $espace = Espace::factory()->create(['abonnement_actif' => true, 'tarif_horaire' => 5000]);
        $formule = $espace->abonnementFormules()->create(['nombre_seances' => $nombreSeances, 'prix' => $prix, 'actif' => true]);

        return [$espace, $formule];
    }

    public function test_creer_un_abonnement_genere_une_reservation_par_seance(): void
    {
        [$espace, $formule] = $this->espaceAvecFormule();
        $client = User::factory()->client()->create();

        $abonnement = app(AbonnementService::class)->creer($client, $espace, $formule, $this->creneauProchain(), 1.0);

        $this->assertSame('en_attente', $abonnement->statut);
        $this->assertSame(4, $abonnement->nombre_seances);
        $this->assertEqualsWithDelta(20000.0, (float) $abonnement->prix_total, 0.01);
        $this->assertEqualsWithDelta(6000.0, (float) $abonnement->montant_acompte, 0.01); // 30 %

        $this->assertSame(4, $abonnement->reservations()->count());
        $this->assertSame(4, Reservation::where('abonnement_id', $abonnement->id)->where('statut', 'en_attente')->count());

        // Les montants des séances doivent se recomposer exactement au total de l'abonnement.
        $this->assertEqualsWithDelta(
            (float) $abonnement->prix_total,
            (float) $abonnement->reservations()->sum('prix_total'),
            0.01,
        );

        // Une séance par semaine à partir de la date choisie.
        $dates = $abonnement->reservations()->orderBy('date_debut')->pluck('date_debut');
        $this->assertTrue($dates->first()->equalTo($this->creneauProchain()));
        $this->assertTrue($dates->get(1)->equalTo($this->creneauProchain()->addWeek()));
    }

    public function test_creation_refusee_si_une_seance_est_deja_prise(): void
    {
        [$espace, $formule] = $this->espaceAvecFormule(nombreSeances: 3);
        $client = User::factory()->client()->create();
        $premierDebut = $this->creneauProchain();

        // Une réservation existante bloque la 3e occurrence (semaine +2).
        Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'confirmee',
            'date_debut' => $premierDebut->addWeeks(2),
            'date_fin' => $premierDebut->addWeeks(2)->addHour(),
        ]);

        $this->expectException(ValidationException::class);

        app(AbonnementService::class)->creer($client, $espace, $formule, $premierDebut, 1.0);

        $this->assertSame(0, \App\Models\Abonnement::count());
    }

    public function test_espace_sans_abonnement_actif_refuse_la_creation(): void
    {
        $espace = Espace::factory()->create(['abonnement_actif' => false]);
        $formule = $espace->abonnementFormules()->create(['nombre_seances' => 4, 'prix' => 20000, 'actif' => true]);
        $client = User::factory()->client()->create();

        $this->expectException(ValidationException::class);

        app(AbonnementService::class)->creer($client, $espace, $formule, $this->creneauProchain(), 1.0);
    }

    public function test_paiement_confirme_toutes_les_seances_dun_coup(): void
    {
        [$espace, $formule] = $this->espaceAvecFormule();
        $client = User::factory()->client()->create();

        $abonnement = app(AbonnementService::class)->creer($client, $espace, $formule, $this->creneauProchain(), 1.0);

        app(PaiementService::class)->initierAbonnement($abonnement, 'yenga', $client->telephone);
        $abonnement->refresh();

        $this->assertSame('confirmee', $abonnement->statut);
        $this->assertSame(4, $abonnement->reservations()->where('statut', 'confirmee')->count());

        // Un seul paiement, rattaché à la première séance.
        $this->assertSame(1, \App\Models\Paiement::where('reservation_id', $abonnement->premiereSeance()->id)->count());

        // Une seule notification « abonnement confirmé », pas une par séance.
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $client->id,
            'type' => 'abonnement_confirme',
        ]);
        $this->assertSame(
            1,
            \App\Models\AppNotification::where('user_id', $client->id)->where('type', 'abonnement_confirme')->count(),
        );
    }

    public function test_annulation_ne_touche_pas_les_seances_deja_passees(): void
    {
        [$espace, $formule] = $this->espaceAvecFormule(nombreSeances: 2);
        $client = User::factory()->client()->create();

        $abonnement = app(AbonnementService::class)->creer($client, $espace, $formule, $this->creneauProchain(), 1.0);
        app(PaiementService::class)->initierAbonnement($abonnement, 'yenga', $client->telephone);
        $abonnement->refresh();

        // La première séance est déjà passée et terminée.
        $premiere = $abonnement->reservations()->orderBy('date_debut')->first();
        $premiere->update(['statut' => 'terminee']);

        app(AbonnementService::class)->annuler($abonnement, $client, 'Changement de programme');
        $abonnement->refresh();

        $this->assertSame('annulee', $abonnement->statut);
        $this->assertSame('terminee', $premiere->fresh()->statut);

        $seconde = $abonnement->reservations()->orderBy('date_debut')->skip(1)->first();
        $this->assertSame('annulee', $seconde->fresh()->statut);
    }
}
