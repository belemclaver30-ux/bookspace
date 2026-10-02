<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReservationResource;
use App\Models\DemandeModification;
use App\Models\Reservation;
use App\Services\DemandeService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Demandes du client (annulation / report d'une réservation confirmée),
 * soumises à la validation du gérant.
 */
class DemandeController extends Controller
{
    public function __construct(private readonly DemandeService $service) {}

    public function store(Request $request, Reservation $reservation): ReservationResource
    {
        abort_unless($reservation->client_id === $request->user()->id, 403);

        $data = $request->validate([
            'type' => ['required', Rule::in(['annulation', 'report'])],
            'motif' => ['nullable', 'string', 'max:500'],
            'date_debut' => ['required_if:type,report', 'nullable', 'date', 'after:now'],
            'duree_heures' => ['nullable', 'numeric', 'min:1', 'max:12'],
        ]);

        $this->service->creer(
            $reservation,
            $request->user(),
            $data['type'],
            $data['motif'] ?? null,
            isset($data['date_debut']) ? CarbonImmutable::parse($data['date_debut']) : null,
            isset($data['duree_heures']) ? (float) $data['duree_heures'] : null,
        );

        return new ReservationResource(
            $reservation->fresh()->load(['espace.photos', 'paiement', 'evaluation', 'derniereDemande'])
        );
    }

    public function retirer(Request $request, DemandeModification $demande): ReservationResource
    {
        abort_unless($demande->demandeur_id === $request->user()->id, 403);

        $this->service->retirer($demande);

        return new ReservationResource(
            $demande->reservation->fresh()->load(['espace.photos', 'paiement', 'evaluation', 'derniereDemande'])
        );
    }
}
