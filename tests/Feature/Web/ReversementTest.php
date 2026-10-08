<?php

namespace Tests\Feature\Web;

use App\Livewire\Admin\Gerants;
use App\Models\AppNotification;
use App\Models\Espace;
use App\Models\Gerant;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReversementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Gerant $gerant;

    private Espace $espace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->gerant = Gerant::factory()->valide()->create([
            'raison_sociale' => 'Studio Harmonie',
            'momo_operateur' => 'orange_money',
            'momo_numero' => '+22670112233',
            'momo_titulaire' => 'Awa Ouédraogo',
        ]);
        $this->espace = Espace::factory()->create(['gerant_id' => $this->gerant->id]);
    }

    /** Réservation en ligne : part du gérant = 2 700 FCFA (factory). */
    private function reservation(string $statut, array $attributs = []): Reservation
    {
        return Reservation::factory()->create(['espace_id' => $this->espace->id, 'statut' => $statut] + $attributs);
    }

    public function test_la_page_liste_les_gerants_et_leurs_soldes(): void
    {
        $this->reservation('terminee');
        $this->reservation('terminee');
        $this->reservation('confirmee');
        $this->reservation('annulee');
        $this->reservation('terminee', ['origine' => 'gerant']); // encaissée sur place

        $this->actingAs($this->admin)->get('/admin/gerants')
            ->assertOk()
            ->assertSee('Studio Harmonie')
            ->assertSee('+22670112233')
            ->assertSee('5 400 FCFA')  // à reverser : 2 réservations terminées
            ->assertSee('2 700 FCFA'); // à venir
    }

    public function test_enregistrer_un_versement(): void
    {
        $terminees = [$this->reservation('terminee'), $this->reservation('terminee')];
        $aVenir = $this->reservation('confirmee');

        Livewire::actingAs($this->admin)->test(Gerants::class)
            ->call('ouvrirVersement', $this->gerant->id)
            ->set('reference', 'OM-123456')
            ->call('enregistrerVersement')
            ->assertHasNoErrors()
            ->assertSee('Versement de 5 400 FCFA');

        $reversement = $this->gerant->reversements()->sole();
        $this->assertEquals(5400, $reversement->montant);
        $this->assertSame(2, $reversement->nb_reservations);
        $this->assertSame('+22670112233', $reversement->momo_numero);
        $this->assertSame($this->admin->id, $reversement->effectue_par);

        foreach ($terminees as $r) {
            $this->assertSame($reversement->id, $r->fresh()->reversement_id);
        }
        $this->assertNull($aVenir->fresh()->reversement_id);

        $this->assertTrue(AppNotification::where('user_id', $this->gerant->user_id)
            ->where('type', 'reversement_effectue')->exists());

        // Plus rien à reverser : un second versement est refusé.
        Livewire::actingAs($this->admin)->test(Gerants::class)
            ->set('versement', $this->gerant->id)
            ->set('reference', 'OM-999')
            ->call('enregistrerVersement')
            ->assertHasErrors('reference');
        $this->assertSame(1, $this->gerant->reversements()->count());
    }

    public function test_pas_de_versement_sans_compte_mobile_money(): void
    {
        $this->gerant->update(['momo_operateur' => null, 'momo_numero' => null]);
        $this->reservation('terminee');

        Livewire::actingAs($this->admin)->test(Gerants::class)
            ->assertSee('Aucun compte Mobile Money renseigné')
            ->set('versement', $this->gerant->id)
            ->set('reference', 'OM-1')
            ->call('enregistrerVersement')
            ->assertHasErrors('reference');

        $this->assertSame(0, $this->gerant->reversements()->count());
    }

    public function test_la_reference_est_obligatoire(): void
    {
        $this->reservation('terminee');

        Livewire::actingAs($this->admin)->test(Gerants::class)
            ->call('ouvrirVersement', $this->gerant->id)
            ->call('enregistrerVersement')
            ->assertHasErrors(['reference' => 'required']);
    }

    public function test_le_gerant_voit_ses_versements(): void
    {
        $this->reservation('terminee');

        $this->actingAs($this->gerant->user)->get('/profil/revenus')
            ->assertOk()->assertSee('En attente de versement')->assertSee('2 700 FCFA');
    }

    public function test_reserve_a_l_administrateur(): void
    {
        $this->actingAs($this->gerant->user)->get('/admin/gerants')->assertRedirect();
    }
}
