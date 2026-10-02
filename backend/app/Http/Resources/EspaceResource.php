<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'nom' => $this->nom,
            'description' => $this->description,
            'adresse' => $this->adresse,
            'ville' => $this->ville,
            'tarif_horaire' => (float) $this->tarif_horaire,
            'statut' => $this->statut,
            'equipements' => $this->equipements ?? [],
            'note_moyenne' => (float) $this->note_moyenne,
            'nb_avis' => $this->nb_avis,
            'mis_en_avant' => $this->mis_en_avant,
            'position' => $this->latitude !== null ? ['lat' => (float) $this->latitude, 'lng' => (float) $this->longitude] : null,
            'distance_km' => $this->whenNotNull($this->resource->distance_km ?? null),

            'salle' => $this->when($this->type === 'salle_repetition', fn () => [
                'capacite' => $this->capacite,
                'isolation_phonique' => $this->isolation_phonique,
            ]),
            'terrain' => $this->when($this->type === 'terrain_foot', fn () => [
                'type_pelouse' => $this->type_pelouse,
                'eclairage' => $this->eclairage,
                'dimensions' => $this->dimensions,
            ]),

            'photos' => $this->whenLoaded('photos', fn () => $this->photos->pluck('url')),
            'photo_principale' => $this->whenLoaded('photos', fn () => $this->photos->first()?->url),
            // Détail (id + url) pour la gestion des photos côté gérant.
            'medias' => $this->whenLoaded('photos', fn () => $this->photos->map(fn ($p) => ['id' => $p->id, 'url' => $p->url])),
            'gerant' => $this->whenLoaded('gerant', fn () => [
                'id' => $this->gerant->id,
                'raison_sociale' => $this->gerant->raison_sociale,
                'verifie' => $this->gerant->estValide(),
            ]),
            'disponibilites' => $this->whenLoaded('disponibilites', fn () => $this->disponibilites->map(fn ($d) => [
                'jour_semaine' => $d->jour_semaine,
                'heure_ouverture' => substr($d->heure_ouverture, 0, 5),
                'heure_fermeture' => substr($d->heure_fermeture, 0, 5),
            ])),
            'avis' => EvaluationResource::collection($this->whenLoaded('evaluations')),
        ];
    }
}
