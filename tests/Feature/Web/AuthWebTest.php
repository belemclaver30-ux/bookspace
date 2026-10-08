<?php

namespace Tests\Feature\Web;

use App\Livewire\Auth\Connexion;
use App\Livewire\Auth\Inscription;
use App\Models\Gerant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_visiteur_voit_l_accueil_public_et_les_formulaires(): void
    {
        $this->get('/')->assertOk()->assertSee('BookSpace')->assertSee('Se connecter');
        $this->get('/connexion')->assertOk()->assertSeeLivewire(Connexion::class);
        $this->get('/inscription')->assertOk()->assertSeeLivewire(Inscription::class);
    }

    public function test_les_pages_protegees_renvoient_vers_la_connexion(): void
    {
        foreach (['/app', '/reservations', '/gerant', '/admin', '/profil', '/notifications'] as $url) {
            $this->get($url)->assertRedirect('/connexion');
        }
    }

    public function test_connexion_par_telephone_et_redirection_selon_le_role(): void
    {
        User::factory()->gerant()->create(['telephone' => '+22670000010']);

        // Le numéro est accepté avec ou sans espaces.
        Livewire::test(Connexion::class)
            ->set('telephone', '+226 70 00 00 10')
            ->set('password', 'password')
            ->call('connecter')
            ->assertRedirect('/gerant');

        $this->assertAuthenticated();
    }

    public function test_mauvais_mot_de_passe_refuse(): void
    {
        User::factory()->client()->create(['telephone' => '+22670000001']);

        Livewire::test(Connexion::class)
            ->set('telephone', '+22670000001')
            ->set('password', 'mauvais')
            ->call('connecter')
            ->assertHasErrors('telephone')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    /** Force brute répartie sur plusieurs adresses IP : bloquée par numéro. */
    public function test_force_brute_bloquee_par_numero(): void
    {
        User::factory()->client()->create(['telephone' => '+22670000001']);

        for ($i = 1; $i <= 20; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])
                ->postJson('/api/auth/login', ['telephone' => '+22670000001', 'password' => "essai{$i}"])
                ->assertStatus(422);
        }

        // Même avec le bon mot de passe et une nouvelle adresse : refusé.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
            ->postJson('/api/auth/login', ['telephone' => '+22670000001', 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonPath('errors.telephone.0', fn ($m) => str_starts_with($m, 'Trop de tentatives'));
    }

    public function test_api_login_limite_par_adresse_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/auth/login', ['telephone' => "+2267000{$i}999", 'password' => 'x']);
        }

        $this->postJson('/api/auth/login', ['telephone' => '+22670009999', 'password' => 'x'])->assertStatus(429);
    }

    public function test_entetes_de_securite(): void
    {
        $this->get('/connexion')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_compte_desactive_refuse(): void
    {
        User::factory()->client()->create(['telephone' => '+22670000001', 'actif' => false]);

        Livewire::test(Connexion::class)
            ->set('telephone', '+22670000001')
            ->set('password', 'password')
            ->call('connecter')
            ->assertHasErrors('telephone');

        $this->assertGuest();
    }

    public function test_inscription_client(): void
    {
        Livewire::test(Inscription::class)
            ->set('name', 'Konan Marcel')
            ->set('telephone', '+226 70 00 00 01')
            ->set('password', 'motdepasse')
            ->set('accepte', true)
            ->call('inscrire')
            ->assertHasNoErrors()
            ->assertRedirect('/app');

        $this->assertDatabaseHas('users', ['telephone' => '+22670000001', 'role' => 'client']);
        $this->assertAuthenticated();
    }

    public function test_inscription_gerant_cree_un_profil_en_attente(): void
    {
        Livewire::test(Inscription::class)
            ->set('role', 'gerant')
            ->set('name', 'Awa Traoré')
            ->set('raison_sociale', 'Studio Harmonie SARL')
            ->set('telephone', '+22670000010')
            ->set('password', 'motdepasse')
            ->set('accepte', true)
            ->call('inscrire')
            ->assertRedirect('/gerant');

        $this->assertDatabaseHas('gerants', ['raison_sociale' => 'Studio Harmonie SARL', 'statut_validation' => 'en_attente']);
    }

    public function test_inscription_exige_conditions_et_raison_sociale(): void
    {
        Livewire::test(Inscription::class)
            ->set('role', 'gerant')
            ->set('name', 'Awa Traoré')
            ->set('telephone', '+22670000010')
            ->set('password', 'motdepasse')
            ->call('inscrire')
            ->assertHasErrors(['accepte', 'raison_sociale']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_chaque_role_est_renvoye_vers_son_espace(): void
    {
        $client = User::factory()->client()->create();
        $gerant = Gerant::factory()->valide()->create()->user;
        $admin = User::factory()->admin()->create();

        $this->actingAs($client)->get('/gerant')->assertRedirect('/app');
        $this->actingAs($client)->get('/admin')->assertRedirect('/app');
        $this->actingAs($gerant)->get('/app')->assertRedirect('/gerant');
        $this->actingAs($gerant)->get('/admin/parametres')->assertRedirect('/gerant');
        $this->actingAs($admin)->get('/gerant/espaces')->assertRedirect('/admin');

        // Un utilisateur connecté ne revoit pas l'écran d'accueil public.
        $this->actingAs($admin)->get('/')->assertRedirect('/admin');
        $this->actingAs($client)->get('/connexion')->assertRedirect('/app');
    }

    public function test_deconnexion(): void
    {
        $this->actingAs(User::factory()->client()->create())
            ->post('/deconnexion')
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
