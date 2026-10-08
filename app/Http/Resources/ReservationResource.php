<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'statut' => $this->statut,
            'date_debut' => $this->date_debut?->toIso8601String(),
            'date_fin' => $this->date_fin?->toIso8601String(),
            'duree_heures' => (float) $this->duree_heures,
            'prix_total' => (float) $this->prix_total,
            'taux_acompte' => (float) $this->taux_acompte,
            'montant_acompte' => (float) $this->montant_acompte,
            'montant_commission' => (float) $this->montant_commission,
            'montant_reverse_gerant' => (float) $this->montant_reverse_gerant,
            'motif_annulation' => $this->motif_annulation,
            'annulable' => $this->estAnnulable(),
            'reportable' => $this->estReportable(),
            'a_evalue' => $this->relationLoaded('evaluation') && $this->evaluation !== null,
            // Le client peut évaluer une réservation terminée pas encore évaluée.
            'evaluable' => $this->statut === 'terminee'
                && ! ($this->relationLoaded('evaluation') && $this->evaluation !== null),
            'mon_avis' => $this->when(
                $this->relationLoaded('evaluation') && $this->evaluation !== null,
                fn () => ['note' => $this->evaluation->note, 'commentaire' => $this->evaluation->commentaire],
            ),
            // Dernière demande de modification (annulation / report) du client.
            'demande' => $this->when(
                $this->relationLoaded('derniereDemande') && $this->derniereDemande !== null,
                fn () => [
                    'id' => $this->derniereDemande->id,
                    'type' => $this->derniereDemande->type,
                    'statut' => $this->derniereDemande->statut,
                    'motif' => $this->derniereDemande->motif,
                    'nouvelle_date_debut' => $this->derniereDemande->nouvelle_date_debut?->toIso8601String(),
                    'reponse_gerant' => $this->derniereDemande->reponse_gerant,
                ],
            ),
            'confirmee_at' => $this->confirmee_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),

            'espace' => new EspaceResource($this->whenLoaded('espace')),
            // Réservation "compte" (client_id) → nom/tél. chargés via la relation ;
            // réservation manuelle du gérant (sans compte) → colonnes libres.
            'client' => $this->client_id !== null
                ? $this->whenLoaded('client', fn () => [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'telephone' => $this->client->telephone,
                ])
                : ($this->client_nom !== null ? [
                    'name' => $this->client_nom,
                    'telephone' => $this->client_telephone,
                ] : null),
            'origine' => $this->origine ?? 'client',
            'paiement' => $this->whenLoaded('paiement', fn () => $this->paiement ? [
                'statut' => $this->paiement->statut,
                'mode' => $this->paiement->mode,
                'montant' => (float) $this->paiement->montant,
                'reference_operateur' => $this->paiement->reference_operateur,
                // Lien de paiement hébergé (Yenga) tant que le paiement n'est
                // pas tranché — le front y redirige/reprend le client.
                'lien_paiement' => $this->paiement->statut === 'en_cours'
                    ? ($this->paiement->payload['demande']['instructions'] ?? null)
                    : null,
                'paye_at' => $this->paiement->paye_at?->toIso8601String(),
            ] : null),
        ];
    }
}
