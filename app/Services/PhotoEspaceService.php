<?php

namespace App\Services;

use App\Models\Espace;
use App\Models\EspacePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Stockage des photos réelles d'un espace (TDR §5.1 « photos »). */
class PhotoEspaceService
{
    public function __construct(private readonly ImageConversionService $images) {}

    /** @param iterable<UploadedFile> $fichiers */
    public function ajouter(Espace $espace, iterable $fichiers): void
    {
        $ordre = (int) $espace->photos()->max('ordre');
        $disque = config('bookspace.photos_disk', 'public');

        foreach ($fichiers as $fichier) {
            // Converties en WebP à l'ajout : poids nettement réduit, temps de
            // chargement des fiches espace amélioré côté client.
            $nom = Str::random(40) . '.' . $this->images->extension();
            $chemin = "espaces/{$espace->id}/{$nom}";
            Storage::disk($disque)->put($chemin, $this->images->versWebp($fichier));
            $espace->photos()->create(['chemin' => $chemin, 'ordre' => ++$ordre]);
        }
    }

    public function supprimer(EspacePhoto $photo): void
    {
        if (! str_starts_with($photo->chemin, 'http')) {
            Storage::disk(config('bookspace.photos_disk', 'public'))->delete($photo->chemin);
        }

        $photo->delete();
    }

    /** Fait d'une photo la photo principale (ordre le plus petit). */
    public function definirPrincipale(Espace $espace, EspacePhoto $photo): void
    {
        $photo->update(['ordre' => ((int) $espace->photos()->min('ordre')) - 1]);
    }
}
