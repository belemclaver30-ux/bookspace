<?php

namespace App\Http\Controllers\Api\Gerant;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReservationResource;
use App\Models\Espace;
use App\Models\Reservation;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/** Réservations reçues par le gérant (TDR §5.2). */
class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $gerant = $request->user()->gerant()->firstOrFail();

        $data = $request->validate([
            'statut' => ['nullable', 'string'],
            'espace_id' => ['nullable', 'integer'],
            // Fenêtre temporelle : alimente la vue calendrier du gérant.
            'du' => ['nullable', 'date'],
            'au' => ['nullable', 'date', 'after_or_equal:du'],
        ]);

        $query = Reservation::where('gerant_id', $gerant->id)
            ->with(['espace.photos', 'client', 'paiement'])
            ->when($data['statut'] ?? null, fn ($q, $s) => $q->where('statut', $s))
            ->when($data['espace_id'] ?? null, fn ($q, $id) => $q->where('espace_id', $id))
            ->when($data['du'] ?? null, fn ($q, $d) => $q->where('date_debut', '>=', $d))
            ->when($data['au'] ?? null, fn ($q, $d) => $q->where('date_debut', '<=', $d . ' 23:59:59'))
            ->orderBy('date_debut');

        // Fenêtre bornée (un mois) -> on renvoie tout ; sinon pagination.
        if (isset($data['du'], $data['au'])) {
            return ReservationResource::collection($query->get());
        }

        return ReservationResource::collection($query->reorder()->latest('date_debut')->paginate(20));
    }

    /** Réservation créée à la main par le gérant (client au comptoir/téléphone). */
    public function store(Request $request, Espace $espace, ReservationService $service): ReservationResource
    {
        abort_unless($espace->gerant_id === $request->user()->gerant?->id, 403);

        $data = $request->validate([
            'date_debut' => ['required', 'date', 'after:now'],
            'duree_heures' => ['required', 'numeric', 'min:1', 'max:12'],
            'client_nom' => ['required', 'string', 'max:255'],
            'client_telephone' => ['required', 'string', 'max:30'],
        ]);

        $reservation = $service->creerManuelle(
            $espace,
            CarbonImmutable::parse($data['date_debut']),
            (float) $data['duree_heures'],
            $data['client_nom'],
            $data['client_telephone'],
            $request->user(),
        );

        return new ReservationResource($reservation->load(['espace.photos', 'paiement']));
    }

    public function show(Request $request, Reservation $reservation): ReservationResource
    {
        $this->autoriser($request, $reservation);

        return new ReservationResource(
            $reservation->load(['espace.photos', 'client', 'paiement'])
        );
    }

    public function annuler(Request $request, Reservation $reservation, ReservationService $service): ReservationResource
    {
        $this->autoriser($request, $reservation);

        $data = $request->validate(['motif' => ['required', 'string', 'max:500']]);
        $service->annuler($reservation, $request->user(), $data['motif']);

        return new ReservationResource($reservation->fresh()->load(['espace.photos', 'client', 'paiement', 'evaluation']));
    }

    /** Reporte la réservation à un autre créneau (TDR §5.2 : gérer le calendrier). */
    public function reporter(Request $request, Reservation $reservation, ReservationService $service): ReservationResource
    {
        $this->autoriser($request, $reservation);

        $data = $request->validate([
            'date_debut' => ['required', 'date', 'after:now'],
            'duree_heures' => ['nullable', 'numeric', 'min:1', 'max:12'],
        ]);

        $service->reporter(
            $reservation,
            $request->user(),
            \Carbon\CarbonImmutable::parse($data['date_debut']),
            isset($data['duree_heures']) ? (float) $data['duree_heures'] : null,
        );

        return new ReservationResource($reservation->fresh()->load(['espace.photos', 'client', 'paiement', 'evaluation']));
    }

    private function autoriser(Request $request, Reservation $reservation): void
    {
        abort_unless($reservation->gerant_id === $request->user()->gerant?->id, 403);
    }
}
