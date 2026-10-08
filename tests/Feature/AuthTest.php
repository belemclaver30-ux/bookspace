<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_client_peut_s_inscrire(): void
    {
        $reponse = $this->postJson('/api/auth/register', [
            'name' => 'Konan Marcel',
            'telephone' => '+22670000001',
            'password' => 'motdepasse',
            'role' => 'client',
        ]);

        $reponse->assertCreated()
            ->assertJsonPath('data.role', 'client')
            ->assertJsonStructure(['token', 'data' => ['id', 'telephone']]);

        $this->assertDatabaseHas('users', ['telephone' => '+22670000001', 'role' => 'client']);
    }

    public function test_un_gerant_s_inscrit_avec_un_profil_en_attente(): void
    {
        $reponse = $this->postJson('/api/auth/register', [
            'name' => 'Studio Harmonie',
            'telephone' => '+22670000010',
            'password' => 'motdepasse',
            'role' => 'gerant',
            'raison_sociale' => 'Studio Harmonie SARL',
        ]);

        $reponse->assertCreated()->assertJsonPath('data.gerant.statut_validation', 'en_attente');
        $this->assertDatabaseHas('gerants', ['raison_sociale' => 'Studio Harmonie SARL', 'statut_validation' => 'en_attente']);
    }

    public function test_le_gerant_doit_fournir_une_raison_sociale(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'X', 'telephone' => '+22670000011', 'password' => 'motdepasse', 'role' => 'gerant',
        ])->assertStatus(422)->assertJsonValidationErrorFor('raison_sociale');
    }

    public function test_connexion_avec_mauvais_mot_de_passe_refusee(): void
    {
        User::factory()->create(['telephone' => '+22670000002', 'password' => Hash::make('bon')]);

        $this->postJson('/api/auth/login', ['telephone' => '+22670000002', 'password' => 'mauvais'])
            ->assertStatus(422);
    }

    public function test_me_exige_un_jeton(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }
}
