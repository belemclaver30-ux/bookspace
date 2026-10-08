<?php

namespace App\Livewire\Client;

use App\Models\Espace;
use App\Services\GeoService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Accueil client : recherche et liste des espaces publiés (TDR §5.1 :
 * « rechercher un espace par ville, type… »), avec tri par proximité.
 */
#[Layout('components.layouts.client')]
#[Title('Accueil')]
class Accueil extends Component
{
    private const PAR_PAGE = 15;

    #[Url(as: 'type')]
    public string $filtre = 'tous';

    #[Url]
    public string $q = '';

    /** Tri « Près de moi » actif (nécessite la position du client). */
    #[Session]
    public bool $proche = false;

    #[Session]
    public ?float $lat = null;

    #[Session]
    public ?float $lng = null;

    /** Nombre d'espaces affichés (« Voir plus ») : dans l'URL pour le retour arrière. */
    #[Url(as: 'n', except: self::PAR_PAGE)]
    public int $limite = self::PAR_PAGE;

    public function filtrer(string $type): void
    {
        $this->filtre = in_array($type, ['salle_repetition', 'terrain_foot'], true) ? $type : 'tous';
        $this->limite = self::PAR_PAGE;
    }

    /** Toute nouvelle recherche repart du début de la liste. */
    public function updatedQ(): void
    {
        $this->limite = self::PAR_PAGE;
    }

    public function activerProximite(float $lat, float $lng): void
    {
        abort_unless(abs($lat) <= 90 && abs($lng) <= 180, 422);

        $this->lat = $lat;
        $this->lng = $lng;
        $this->proche = true;
        $this->limite = self::PAR_PAGE;
    }

    public function desactiverProximite(): void
    {
        $this->proche = false;
        $this->limite = self::PAR_PAGE;
    }

    public function voirPlus(): void
    {
        $this->limite += self::PAR_PAGE;
    }

    public function render()
    {
        $type = $this->filtre === 'tous' ? null : $this->filtre;
        $base = Espace::query()->publies()->with('photos')->recherche(null, $type, trim($this->q) ?: null);
        $position = $this->lat !== null && $this->lng !== null;

        if ($this->proche && $position) {
            // Distance calculée en PHP (portable MySQL/SQLite), tri croissant.
            $tous = $base->whereNotNull('latitude')->whereNotNull('longitude')->get()
                ->each(fn (Espace $e) => $e->distance_km = $this->distance($e))
                ->sortBy('distance_km')
                ->values();
            $total = $tous->count();
            $espaces = $tous->take($this->limite);
        } else {
            $total = (clone $base)->count();
            $espaces = $base->orderByDesc('mis_en_avant')->orderByDesc('note_moyenne')->limit($this->limite)->get();

            if ($position) {
                $espaces->each(function (Espace $e) {
                    if ($e->latitude !== null && $e->longitude !== null) {
                        $e->distance_km = $this->distance($e);
                    }
                });
            }
        }

        return view('livewire.client.accueil', [
            'espaces' => $espaces,
            'total' => $total,
            'reste' => $espaces->count() < $total,
        ]);
    }

    private function distance(Espace $espace): float
    {
        return GeoService::distanceKm($this->lat, $this->lng, (float) $espace->latitude, (float) $espace->longitude);
    }
}
