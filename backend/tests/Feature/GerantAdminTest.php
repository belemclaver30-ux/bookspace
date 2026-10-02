<?php

namespace Tests\Feature;

use App\Models\Espace;
use App\Models\Gerant;
use App\Models\Parametre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GerantAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_gerant_non_valide_ne_peut_pas_creer_d_espace(): void
    {
        $gerant = Gerant::factory()->create(); // en_attente

        $this->actingAs($gerant->user)->postJson('/api/gerant/espaces', [
            'type' => 'salle_repetition', 'nom' => 'Test', 'adresse' => 'X', 'ville' => 'Ouaga', 'tarif_horaire' => 5000,
        ])->assertForbidden();
    }

    public function test_espace_cree_par_gerant_puis_valide_par_admin_devient_visible(): void
    {
        $gerant = Gerant::factory()->valide()->create();
        $admin = User::factory()->admin()->create();

        $espaceId = $this->actingAs($gerant->user)->postJson('/api/gerant/espaces', [
            'type' => 'salle_repetition', 'nom' => 'Studio Neuf', 'adresse' => 'Rue 1', 'ville' => 'Ouagadougou',
            'tarif_horaire' => 6000, 'equipements' => ['WiFi'],
        ])->assertCreated()->assertJsonPath('data.statut', 'en_attente')->json('data.id');

        // Invisible tant que non validé
        $this->getJson('/api/espaces')->assertJsonCount(0, 'data');

        $this->actingAs($admin)->postJson("/api/admin/espaces/{$espaceId}/valider")
            ->assertOk()->assertJsonPath('data.statut', 'valide');

        $this->getJson('/api/espaces')->assertJsonCount(1, 'data');
    }

    public function test_admin_valide_un_gerant(): void
    {
        $gerant = Gerant::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson("/api/admin/gerants/{$gerant->id}/valider")->assertOk();

        $this->assertSame('valide', $gerant->fresh()->statut_validation);
    }

    public function test_un_client_n_atteint_pas_les_routes_admin(): void
    {
        $this->actingAs(User::factory()->client()->create())
            ->getJson('/api/admin/tableau-de-bord')->assertForbidden();
    }

    public function test_admin_modifie_le_taux_de_commission(): void
    {
        Parametre::definir('commission_rate', '10');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->putJson('/api/admin/parametres/commission_rate', ['valeur' => '15'])
            ->assertOk();

        $this->assertSame('15', Parametre::valeur('commission_rate'));
    }

    public function test_statistiques_gerant_agregent_les_reservations_confirmees(): void
    {
        $gerant = Gerant::factory()->valide()->create();
        $espace = Espace::factory()->for($gerant)->create(['tarif_horaire' => 5000]);

        \App\Models\Reservation::factory()->for($espace)->create([
            'gerant_id' => $gerant->id, 'statut' => 'confirmee',
            'prix_total' => 10000, 'montant_acompte' => 3000, 'montant_commission' => 300,
            'montant_reverse_gerant' => 2700, 'duree_heures' => 2,
        ]);

        $this->actingAs($gerant->user)->getJson('/api/gerant/statistiques')
            ->assertOk()
            ->assertJsonPath('revenus.chiffre_affaires', 10000)
            ->assertJsonPath('revenus.commissions_plateforme', 300)
            ->assertJsonPath('reservations.confirmees', 1)
            ->assertJsonStructure(['taux_occupation', 'serie' => [['date', 'chiffre_affaires']], 'repartition_statuts']);
    }

    public function test_reservations_gerant_filtrees_par_fenetre_de_mois(): void
    {
        $gerant = \App\Models\Gerant::factory()->valide()->create();
        $espace = Espace::factory()->for($gerant)->create();

        \App\Models\Reservation::factory()->count(3)->for($espace)->create([
            'gerant_id' => $gerant->id, 'date_debut' => '2026-05-10 10:00:00', 'date_fin' => '2026-05-10 12:00:00',
        ]);
        \App\Models\Reservation::factory()->for($espace)->create([
            'gerant_id' => $gerant->id, 'date_debut' => '2026-06-02 10:00:00', 'date_fin' => '2026-06-02 12:00:00',
        ]);

        // Fenêtre mai -> renvoie les 3, non paginé
        $this->actingAs($gerant->user)
            ->getJson('/api/gerant/reservations?du=2026-05-01&au=2026-05-31')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_le_tableau_de_bord_admin_fournit_les_series_des_graphes(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/tableau-de-bord')
            ->assertOk()
            ->assertJsonStructure([
                'commissions_par_mois' => [['mois', 'commissions']],
                'espaces_par_type' => ['salle_repetition', 'terrain_foot'],
                'reservations_par_statut',
                'top_espaces',
            ]);
    }
}
