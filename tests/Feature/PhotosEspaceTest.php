<?php

namespace Tests\Feature;

use App\Models\Espace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotosEspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_le_gerant_ajoute_des_photos_a_son_espace(): void
    {
        $espace = Espace::factory()->create();

        $reponse = $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/espaces/{$espace->id}/photos", [
                'photos' => [
                    UploadedFile::fake()->create('studio1.jpg', 400, 'image/jpeg'),
                    UploadedFile::fake()->create('studio2.jpg', 400, 'image/jpeg'),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data.medias');

        $this->assertSame(2, $espace->photos()->count());
        Storage::disk('public')->assertExists($espace->photos()->first()->chemin);
        // Chaque média expose id + url exploitables côté front
        $this->assertArrayHasKey('id', $reponse->json('data.medias.0'));
    }

    public function test_un_fichier_non_image_est_refuse(): void
    {
        $espace = Espace::factory()->create();

        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/espaces/{$espace->id}/photos", [
                'photos' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')],
            ])
            ->assertStatus(422);
    }

    public function test_un_autre_gerant_ne_peut_pas_ajouter_de_photo(): void
    {
        $espace = Espace::factory()->create();
        $autre = \App\Models\Gerant::factory()->valide()->create();

        $this->actingAs($autre->user)
            ->postJson("/api/gerant/espaces/{$espace->id}/photos", [
                'photos' => [UploadedFile::fake()->create('x.jpg', 200, 'image/jpeg')],
            ])
            ->assertForbidden();
    }

    public function test_le_gerant_supprime_une_photo(): void
    {
        $espace = Espace::factory()->create();
        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/espaces/{$espace->id}/photos", ['photos' => [UploadedFile::fake()->create('a.jpg', 200, 'image/jpeg')]]);

        $photo = $espace->photos()->first();

        $this->actingAs($espace->gerant->user)
            ->deleteJson("/api/gerant/espaces/{$espace->id}/photos/{$photo->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data.medias');

        Storage::disk('public')->assertMissing($photo->chemin);
    }

    public function test_definir_la_photo_principale_la_place_en_tete(): void
    {
        $espace = Espace::factory()->create();
        $this->actingAs($espace->gerant->user)->postJson("/api/gerant/espaces/{$espace->id}/photos", [
            'photos' => [UploadedFile::fake()->create('1.jpg', 200, 'image/jpeg'), UploadedFile::fake()->create('2.jpg', 200, 'image/jpeg')],
        ]);

        $deuxieme = $espace->photos()->orderBy('ordre')->skip(1)->first();

        $this->actingAs($espace->gerant->user)
            ->postJson("/api/gerant/espaces/{$espace->id}/photos/{$deuxieme->id}/principale")
            ->assertOk()
            ->assertJsonPath('data.medias.0.id', $deuxieme->id);
    }
}
