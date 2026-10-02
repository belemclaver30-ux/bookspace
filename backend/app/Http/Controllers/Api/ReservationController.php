<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReservationResource;
use App\Models\Espace;
use App\Models\Reservation;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Réservations côté client (TDR §5.1 : réserver, consulter « mes réservations »,
 * annuler).
 */
class ReservationController extends Controller
{
    public function __construct(private readonly ReservationService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $reservations = $request->user()
            ->reservations()
            ->with(['espace.photos', 'paiement', 'evaluation', 'derniereDemande'])
            ->when($request->query('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->latest('date_debut')
            ->paginate(15);

        return ReservationResource::collection($reservations);
    }

    public function store(Request $request): ReservationResource
    {
        $data = $request->validate([
            'espace_id' => ['required', 'exists:espaces,id'],
            'date_debut' => ['required', 'date', 'after:now'],
            'duree_heures' => ['required', 'numeric', 'min:1', 'max:12'],
        ]);

        $espace = Espace::publies()->findOrFail($data['espace_id']);

        $reservation = $this->service->creer(
            $request->user(),
            $espace,
            CarbonImmutable::parse($data['date_debut']),
            (float) $data['duree_heures'],
        );

        return new ReservationResource(
            $reservation->load(['espace.photos', 'paiement'])
        );
    }

    public function show(Request $request, Reservation $reservation): ReservationResource
    {
        $this->autoriser($request, $reservation);

        return new ReservationResource(
            $reservation->load(['espace.photos', 'espace.gerant', 'paiement', 'client', 'evaluation', 'derniereDemande'])
        );
    }

    public function cancel(Request $request, Reservation $reservation): ReservationResource
    {
        $this->autoriser($request, $reservation);

        // Une réservation confirmée ne s'annule qu'avec l'accord du gérant :
        // le client passe par une demande (POST /reservations/{r}/demandes).
        abort_if(
            $reservation->statut === 'confirmee',
            422,
            "Réservation confirmée : envoyez une demande d'annulation, le gérant doit la valider.",
        );

        $data = $request->validate(['motif' => ['nullable', 'string', 'max:500']]);

        $this->service->annuler($reservation, $request->user(), $data['motif'] ?? null);

        return new ReservationResource($reservation->fresh()->load(['espace.photos', 'paiement']));
    }

    private function autoriser(Request $request, Reservation $reservation): void
    {
        abort_unless($reservation->client_id === $request->user()->id, 403);
    }
}
