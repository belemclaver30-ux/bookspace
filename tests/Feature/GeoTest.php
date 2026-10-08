<?php

namespace Tests\Feature;

use App\Models\Espace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_recherche_d_adresse_via_nominatim(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                ['display_name' => 'Ouaga 2000, Ouagadougou, Burkina Faso', 'lat' => '12.3', 'lon' => '-1.5'],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/geo/recherche?q=ouaga 2000')
            ->assertOk()
            ->assertJsonPath('data.0.latitude', 12.3)
            ->assertJsonPath('data.0.longitude', -1.5);
    }

    public function test_itineraire_via_osrm(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 4200,
                    'duration' => 600,
                    'geometry' => ['coordinates' => [[-1.51, 12.36], [-1.50, 12.37]]],
                ]],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/geo/itineraire?depart=12.36,-1.51&arrivee=12.37,-1.50')
            ->assertOk()
            ->assertJsonPath('distance_km', 4.2)
            ->assertJsonPath('duree_min', 10)
            ->assertJsonPath('coordonnees.0', [12.36, -1.51]); // remis en [lat, lng]
    }

    public function test_itineraire_exige_des_coordonnees_valides(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/geo/itineraire?depart=abc&arrivee=12,-1')
            ->assertStatus(422);
    }

    public function test_geo_exige_une_authentification(): void
    {
        $this->getJson('/api/geo/recherche?q=ouaga')->assertUnauthorized();
    }

    public function test_recherche_espaces_triee_par_proximite(): void
    {
        // Ouagadougou ~ (12.37, -1.52) ; Bobo ~ (11.18, -4.30)
        $proche = Espace::factory()->create(['nom' => 'Proche', 'latitude' => 12.37, 'longitude' => -1.52]);
        $loin = Espace::factory()->create(['nom' => 'Loin', 'latitude' => 11.18, 'longitude' => -4.30]);
        Espace::factory()->create(['nom' => 'Sans position', 'latitude' => null, 'longitude' => null]);

        $reponse = $this->getJson('/api/espaces?tri=proche&lat=12.37&lng=-1.52')
            ->assertOk()
            ->assertJsonCount(2, 'data'); // celui sans position est exclu

        $this->assertSame('Proche', $reponse->json('data.0.nom'));
        $this->assertSame('Loin', $reponse->json('data.1.nom'));
        $this->assertEqualsWithDelta(0, $reponse->json('data.0.distance_km'), 1);
        $this->assertGreaterThan(200, $reponse->json('data.1.distance_km'));
    }

    public function test_le_gerant_enregistre_la_position_de_son_espace(): void
    {
        $espace = Espace::factory()->create();

        $this->actingAs($espace->gerant->user)
            ->patchJson("/api/gerant/espaces/{$espace->id}", [
                'latitude' => 12.3684,
                'longitude' => -1.5339,
                'adresse' => 'Avenue Kwame Nkrumah',
            ])
            ->assertOk()
            ->assertJsonPath('data.position.lat', 12.3684);

        $this->assertDatabaseHas('espaces', ['id' => $espace->id, 'latitude' => 12.3684]);
    }
}
