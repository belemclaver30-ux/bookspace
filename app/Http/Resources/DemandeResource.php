<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DemandeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'statut' => $this->statut,
            'motif' => $this->motif,
            'nouvelle_date_debut' => $this->nouvelle_date_debut?->toIso8601String(),
            'nouvelle_duree_heures' => $this->nouvelle_duree_heures ? (float) $this->nouvelle_duree_heures : null,
            'reponse_gerant' => $this->reponse_gerant,
            'created_at' => $this->created_at?->toIso8601String(),
            'traitee_at' => $this->traitee_at?->toIso8601String(),

            'reservation' => new ReservationResource($this->whenLoaded('reservation')),
            'demandeur' => $this->whenLoaded('demandeur', fn () => [
                'name' => $this->demandeur->name,
                'telephone' => $this->demandeur->telephone,
            ]),
        ];
    }
}
