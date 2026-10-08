<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'note' => $this->note,
            'commentaire' => $this->commentaire,
            'auteur' => $this->whenLoaded('client', fn () => $this->client->name),
            'date' => $this->created_at?->toIso8601String(),
        ];
    }
}
