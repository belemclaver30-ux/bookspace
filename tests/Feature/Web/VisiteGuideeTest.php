<?php

namespace Tests\Feature\Web;

use App\Livewire\Auth\Inscription;
use App\Livewire\Profil\Aide;
use App\Livewire\VisiteGuidee;
use App\Models\Espace;
use App\Models\Gerant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisiteGuideeTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_nouveau_client_voit_la_visite_sur_l_accueil(): void
    {
        Espace::factory()->create();
        $client = User::factory()->client()->nouveau()->create(['name' => 'Awa Ouédraogo']);

        $this->actingAs($client)->get('/app')
            ->assertOk()
            ->assertSeeLivewire(VisiteGuidee::class)
            ->assertSee('Bienvenue sur BookSpace, Awa !')
            ->assertSee('data-visite="recherche"', false)
            ->assertSee('data-visite="nav-reservations"', false)
            ->assertSee('data-visite="espace"', false);

        // Seulement sur l'accueil.
        $this->get('/reservations')->assertOk()->assertDontSeeLivewire(VisiteGuidee::class);
    }

    public function test_la_visite_n_est_proposee_qu_une_fois(): void
    {
        $client = User::factory()->client()->nouveau()->create();

        Livewire::actingAs($client)->test(VisiteGuidee::class)->call('terminer');

        $this->assertNotNull($client->fresh()->visite_terminee_at);
        $this->actingAs($client->fresh())->get('/app')->assertDontSeeLivewire(VisiteGuidee::class);
    }

    public function test_un_utilisateur_existant_ne_la_voit_pas(): void
    {
        $this->actingAs(User::factory()->client()->create())->get('/app')
            ->assertOk()->assertDontSeeLivewire(VisiteGuidee::class);
    }

    public function test_l_inscription_declenche_la_visite(): void
    {
        Livewire::test(Inscription::class)
            ->set('name', 'Moussa Traoré')
            ->set('telephone', '+22670111222')
            ->set('password', 'motdepasse')
            ->set('accepte', true)
            ->call('inscrire')
            ->assertRedirect('/app');

        $user = User::where('telephone', '+22670111222')->sole();
        $this->assertNull($user->visite_terminee_at);
        $this->actingAs($user)->get('/app')->assertSeeLivewire(VisiteGuidee::class);
    }

    public function test_visite_du_gerant_selon_sa_validation(): void
    {
        $enAttente = Gerant::factory()->create();
        $enAttente->user->forceFill(['visite_terminee_at' => null])->save();

        $this->actingAs($enAttente->user)->get('/gerant')
            ->assertOk()
            ->assertSeeLivewire(VisiteGuidee::class)
            ->assertSee('en cours de validation')
            ->assertSee('Renseigner mon compte')
            ->assertSee('data-visite="nav-espaces"', false)
            ->assertSee('data-visite="menu"', false);

        $valide = Gerant::factory()->valide()->create();
        $valide->user->forceFill(['visite_terminee_at' => null])->save();

        $this->actingAs($valide->user)->get('/gerant')
            ->assertSee('mon premier espace');
    }

    public function test_pas_de_visite_pour_l_administrateur(): void
    {
        $admin = User::factory()->admin()->nouveau()->create();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertDontSeeLivewire(VisiteGuidee::class);
    }

    public function test_revoir_la_visite_depuis_l_aide(): void
    {
        $client = User::factory()->client()->create();

        Livewire::actingAs($client)->test(Aide::class)
            ->assertSee('Revoir la visite guidée')
            ->call('revoirVisite')
            ->assertRedirect('/app');

        $this->assertNull($client->fresh()->visite_terminee_at);
    }
}
