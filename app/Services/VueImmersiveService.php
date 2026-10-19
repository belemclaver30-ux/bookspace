<?php

namespace App\Services;

use App\Models\Espace;
use App\Models\EspaceVue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Vue immersive d'un espace (voir EspaceVue) :
 * - 4 angles pris du centre de l'espace, en tournant d'un quart de tour ;
 * - un panorama 360° facultatif (photo 360° au format 2:1, ou panorama du
 *   téléphone fait sur un tour complet), affiché à la place des angles.
 */
class VueImmersiveService
{
    /** Largeur max d'un panorama : limite de texture de nombreux téléphones. */
    private const LARGEUR_PANORAMA = 4096;

    /** Un panorama 360° est au moins deux fois plus large que haut. */
    private const RATIO_PANORAMA_MIN = 2.0;

    public function __construct(private readonly ImageConversionService $images) {}

    public function definirAngle(Espace $espace, int $position, UploadedFile $fichier): EspaceVue
    {
        abort_unless(array_key_exists($position, EspaceVue::ANGLES), 422);

        return $this->remplacer($espace, 'angle', $position, $this->images->convertir($fichier));
    }

    /** @throws ValidationException si l'image n'a pas les proportions d'un panorama */
    public function definirPanorama(Espace $espace, UploadedFile $fichier): EspaceVue
    {
        $image = $this->images->convertir($fichier, self::LARGEUR_PANORAMA);

        if (! $image['largeur'] || $image['largeur'] / max(1, $image['hauteur']) < self::RATIO_PANORAMA_MIN) {
            throw ValidationException::withMessages([
                'panorama' => 'Cette photo n’est pas un panorama 360° : elle doit être au moins deux fois plus large que haute.',
            ]);
        }

        return $this->remplacer($espace, 'panorama', 0, $image);
    }

    public function supprimer(EspaceVue $vue): void
    {
        Storage::disk($this->disque())->delete($vue->chemin);
        $vue->delete();
    }

    /**
     * Données de la visionneuse côté client : `panorama` (prioritaire) ou
     * `angles` (seulement si les 4 sont présents), sinon null.
     *
     * @return array{type: string, url?: string, largeur?: int, hauteur?: int, angles?: list<array{libelle: string, url: string}>}|null
     */
    public function pourVisionneuse(Espace $espace): ?array
    {
        $vues = $espace->vues;

        if ($panorama = $vues->firstWhere('type', 'panorama')) {
            return ['type' => 'panorama', 'url' => $panorama->url, 'largeur' => $panorama->largeur, 'hauteur' => $panorama->hauteur];
        }

        $angles = $vues->where('type', 'angle')->sortBy('position');

        if ($angles->count() < count(EspaceVue::ANGLES)) {
            return null;
        }

        return [
            'type' => 'angles',
            'angles' => $angles->map(fn (EspaceVue $v) => ['libelle' => EspaceVue::ANGLES[$v->position], 'url' => $v->url])->values()->all(),
        ];
    }

    /** @param array{donnees: string, largeur: ?int, hauteur: ?int} $image */
    private function remplacer(Espace $espace, string $type, int $position, array $image): EspaceVue
    {
        $chemin = "espaces/{$espace->id}/vues/" . Str::random(40) . '.' . $this->images->extension();
        Storage::disk($this->disque())->put($chemin, $image['donnees']);

        $ancienne = $espace->vues()->where('type', $type)->where('position', $position)->first();

        if ($ancienne) {
            $this->supprimer($ancienne);
        }

        return $espace->vues()->create([
            'type' => $type,
            'position' => $position,
            'chemin' => $chemin,
            'largeur' => $image['largeur'] ?? 0,
            'hauteur' => $image['hauteur'] ?? 0,
        ]);
    }

    private function disque(): string
    {
        return config('bookspace.photos_disk', 'public');
    }
}
