<?php

namespace Tests\Feature\Web;

use App\Livewire\Admin\Litiges;
use App\Livewire\Admin\Parametres;
use App\Livewire\Admin\Validations;
use App\Models\Espace;
use App\Models\Gerant;
use App\Models\Litige;
use App\Models\Parametre;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConsoleAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function test_les_pages_admin_s_affichent(): void
    {
        Reservation::factory()->create(['statut' => 'confirmee']);

        $this->get('/admin')->assertOk()->assertSee('Commissions perçues');
        $this->get('/admin/validations')->assertOk()->assertSee('Aucun gérant en attente');
        $this->get('/admin/litiges')->assertOk()->assertSee('Aucun litige');
        $this->get('/admin/parametres')->assertOk();
        $this->get('/profil')->assertOk()->assertSee('Administrateur');
    }

    public function test_validation_et_rejet_d_un_gerant(): void
    {
        $aValider = Gerant::factory()->create(['raison_sociale' => 'Studio Harmonie']);
        $aRejeter = Gerant::factory()->create(['raison_sociale' => 'Beat Lab']);

        Livewire::test(Validations::class)
            ->assertSee('Studio Harmonie')->assertSee('Beat Lab')
            ->call('validerGerant', $aValider->id)
            ->assertDontSee('Studio Harmonie')
            ->call('rejeterGerant', $aRejeter->id, '')            // sans motif : ignoré
            ->assertSee('Beat Lab')
            ->call('rejeterGerant', $aRejeter->id, 'Dossier incomplet')
            ->assertDontSee('Beat Lab');

        $this->assertSame('valide', $aValider->fresh()->statut_validation);
        $this->assertSame($this->admin->id, $aValider->fresh()->valide_par);
        $this->assertSame('rejete', $aRejeter->fresh()->statut_validation);
        $this->assertSame('Dossier incomplet', $aRejeter->fresh()->motif_rejet);
    }

    public function test_validation_rejet_et_suspension_d_un_espace(): void
    {
        $aValider = Espace::factory()->enAttente()->create(['nom' => 'Studio A']);
        $aRejeter = Espace::factory()->enAttente()->create(['nom' => 'Studio B']);

        Livewire::test(Validations::class)
            ->call('afficher', 'espaces')
            ->assertSee('Studio A')->assertSee('Studio B')
            ->call('validerEspace', $aValider->id)
            ->call('rejeterEspace', $aRejeter->id, 'Photos manquantes')
            ->call('suspendreEspace', $aValider->id, 'Signalement client');

        $this->assertSame('rejete', $aRejeter->fresh()->statut);
        $this->assertSame('Photos manquantes', $aRejeter->fresh()->motif_rejet);
        $this->assertSame('suspendu', $aValider->fresh()->statut);
    }

    public function test_mise_a_jour_des_parametres(): void
    {
        Parametre::definir('commission_rate', '10', 'Commission plateforme (%)');

        Livewire::test(Parametres::class)
            ->assertSet('valeurs.commission_rate', '10')
            ->set('valeurs.commission_rate', '150')
            ->call('enregistrer', 'commission_rate')
            ->assertHasErrors('valeurs.commission_rate')
            ->set('valeurs.commission_rate', '12.5')
            ->call('enregistrer', 'commission_rate')
            ->assertHasNoErrors()
            ->assertSet('enregistre', 'commission_rate');

        $this->assertEquals(12.5, Parametre::valeur('commission_rate'));

        Livewire::test(Parametres::class)->call('enregistrer', 'inconnu')->assertNotFound();
    }

    public function test_traitement_d_un_litige(): void
    {
        $reservation = Reservation::factory()->create(['statut' => 'confirmee']);
        $litige = Litige::create([
            'reservation_id' => $reservation->id, 'ouvert_par' => $reservation->client_id,
            'sujet' => 'Salle fermée à mon arrivée', 'description' => 'Personne sur place.', 'statut' => 'ouvert',
        ]);

        Livewire::test(Litiges::class)
            ->assertSee('Salle fermée à mon arrivée')
            ->call('traiter', $litige->id, 'en_cours')
            ->call('traiter', $litige->id, 'resolu', 'Acompte remboursé')
            ->assertSee('Acompte remboursé');

        $litige->refresh();
        $this->assertSame('resolu', $litige->statut);
        $this->assertSame($this->admin->id, $litige->resolu_par);
        $this->assertNotNull($litige->resolu_at);
    }

    public function test_un_client_ne_peut_pas_piloter_un_composant_admin(): void
    {
        $gerant = Gerant::factory()->create();

        $this->actingAs(User::factory()->client()->create())
            ->get('/admin/validations')->assertRedirect('/app');

        $this->assertSame('en_attente', $gerant->fresh()->statut_validation);
    }
}
