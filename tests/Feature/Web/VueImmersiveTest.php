<?php

namespace Tests\Feature\Web;

use App\Livewire\Client\EspaceFiche;
use App\Livewire\Gerant\EspaceGestion;
use App\Models\Espace;
use App\Models\EspaceVue;
use App\Models\Gerant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VueImmersiveTest extends TestCase
{
    use RefreshDatabase;

    private Gerant $gerant;

    private Espace $espace;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->gerant = Gerant::factory()->valide()->create();
        $this->espace = Espace::factory()->create(['gerant_id' => $this->gerant->id]);
    }

    private function gestion()
    {
        return Livewire::actingAs($this->gerant->user)->test(EspaceGestion::class, ['espace' => $this->espace]);
    }

    public function test_le_gerant_ajoute_les_4_angles(): void
    {
        $page = $this->gestion()->assertSee('Vue immersive')->assertSee('0/4');

        foreach ([0, 1, 2, 3] as $position) {
            $page->set("angles.{$position}", UploadedFile::fake()->image("angle{$position}.jpg", 1600, 1200))
                ->assertHasNoErrors();
        }

        $page->assertSee('En ligne');
        $vues = $this->espace->vues()->get();
        $this->assertCount(4, $vues);
        $this->assertSame([0, 1, 2, 3], $vues->pluck('position')->all());
        $vues->each(fn (EspaceVue $v) => Storage::disk('public')->assertExists($v->chemin));
        $this->assertSame(1600, $vues->first()->largeur);
    }

    public function test_reprendre_un_angle_remplace_l_ancien(): void
    {
        $page = $this->gestion()->set('angles.2', UploadedFile::fake()->image('a.jpg', 800, 600));
        $ancien = $this->espace->vues()->sole()->chemin;

        $page->set('angles.2', UploadedFile::fake()->image('b.jpg', 800, 600));

        $this->assertSame(1, $this->espace->vues()->count());
        Storage::disk('public')->assertMissing($ancien);
    }

    public function test_un_panorama_doit_etre_deux_fois_plus_large_que_haut(): void
    {
        $this->gestion()
            ->set('panorama', UploadedFile::fake()->image('pas-pano.jpg', 1200, 900))
            ->assertHasErrors('panorama');
        $this->assertSame(0, $this->espace->vues()->count());

        $this->gestion()
            ->set('panorama', UploadedFile::fake()->image('pano.jpg', 6000, 3000))
            ->assertHasNoErrors()
            ->assertSee('360° en ligne');

        $pano = $this->espace->vues()->where('type', 'panorama')->sole();
        $this->assertSame(4096, $pano->largeur); // réduit pour les téléphones
        $this->assertSame(2048, $pano->hauteur);
    }

    public function test_le_client_voit_la_vue_seulement_quand_elle_est_complete(): void
    {
        $client = User::factory()->client()->create();
        $page = fn () => Livewire::actingAs($client)->test(EspaceFiche::class, ['espace' => $this->espace->fresh()]);

        foreach ([0, 1, 2] as $position) {
            $this->ajouterVue('angle', $position);
        }
        $page()->assertDontSee('Visiter · 4 angles');

        $this->ajouterVue('angle', 3);
        $page()->assertSee('Visiter · 4 angles')->assertSeeHtml('vueImmersive(');

        // Le panorama, s'il existe, prend le pas sur les angles.
        $this->ajouterVue('panorama', 0);
        $page()->assertSee('Vue 360°')->assertDontSee('Visiter · 4 angles');
    }

    public function test_supprimer_une_vue(): void
    {
        $vue = $this->ajouterVue('angle', 0);

        $this->gestion()->call('supprimerVue', $vue->id);

        $this->assertModelMissing($vue);
    }

    public function test_un_gerant_ne_touche_pas_aux_vues_d_un_autre(): void
    {
        $autre = Espace::factory()->create();
        $vue = $autre->vues()->create(['type' => 'angle', 'position' => 0, 'chemin' => 'x.webp', 'largeur' => 1, 'hauteur' => 1]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->gestion()->call('supprimerVue', $vue->id);
    }

    private function ajouterVue(string $type, int $position): EspaceVue
    {
        return $this->espace->vues()->create([
            'type' => $type, 'position' => $position, 'chemin' => "espaces/{$this->espace->id}/vues/{$type}{$position}.webp",
            'largeur' => $type === 'panorama' ? 4096 : 1600, 'hauteur' => $type === 'panorama' ? 2048 : 1200,
        ]);
    }
}
