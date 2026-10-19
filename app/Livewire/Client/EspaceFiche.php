<?php

namespace App\Livewire\Client;

use App\Models\Espace;
use App\Services\GeoService;
use App\Services\VueImmersiveService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use RuntimeException;

/** Fiche d'un espace (TDR §5.1 : « consulter la fiche d'un espace »). */
#[Layout('components.layouts.ecran', ['fond' => 'bg-white'])]
class EspaceFiche extends Component
{
    public Espace $espace;

    public function mount(Espace $espace): void
    {
        abort_unless($espace->statut === 'valide', 404);

        $this->espace = $espace;
    }

    /**
     * Itinéraire routier depuis la position du client, appelé par la carte
     * (Alpine). Renvoie le tracé, ou `erreur` si le service est indisponible.
     */
    #[Renderless]
    public function itineraire(float $lat, float $lng): array
    {
        if (abs($lat) > 90 || abs($lng) > 180 || $this->espace->latitude === null) {
            return ['erreur' => 'Position invalide.'];
        }

        try {
            $route = app(GeoService::class)->itineraire(
                $lat, $lng, (float) $this->espace->latitude, (float) $this->espace->longitude,
            );
        } catch (RuntimeException $e) {
            return ['erreur' => $e->getMessage()];
        }

        $km = round($route['distance_m'] / 1000, 1);
        $minutes = (int) round($route['duree_s'] / 60);

        return [
            'distance' => distance_lisible($km),
            'duree' => duree_lisible($minutes),
            'coordonnees' => $route['coordonnees'],
        ];
    }

    /** Ajoute ou retire l'espace des favoris du client. */
    public function basculerFavori(): void
    {
        abort_unless(auth()->user()->estClient(), 403);

        auth()->user()->favoris()->toggle([$this->espace->id]);
    }

    public function render()
    {
        $this->espace->loadMissing([
            'photos',
            'vues',
            'gerant',
            'evaluations' => fn ($q) => $q->with('client')->latest()->limit(20),
        ]);

        return view('livewire.client.espace-fiche', [
            'favori' => auth()->user()->favoris()->whereKey($this->espace->id)->exists(),
            'immersif' => app(VueImmersiveService::class)->pourVisionneuse($this->espace),
        ])->title($this->espace->nom);
    }
}
