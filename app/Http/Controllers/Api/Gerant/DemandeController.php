<?php

namespace App\Http\Controllers\Api\Gerant;

use App\Http\Controllers\Controller;
use App\Http\Resources\DemandeResource;
use App\Models\DemandeModification;
use App\Services\DemandeService;
use Illuminate\Http\Request;

/** Traitement des demandes client par le gérant (TDR §5.2). */
class DemandeController extends Controller
{
    public function __construct(private readonly DemandeService $service) {}

    public function index(Request $request)
    {
        $gerantId = $request->user()->gerant()->value('id');

        $demandes = DemandeModification::query()
            ->whereHas('reservation', fn ($q) => $q->where('gerant_id', $gerantId))
            ->with(['reservation.espace.photos', 'demandeur'])
            ->when($request->query('statut', 'en_attente'), fn ($q, $s) => $q->where('statut', $s))
            ->latest()
            ->paginate(20);

        return DemandeResource::collection($demandes);
    }

    public function accepter(Request $request, DemandeModification $demande): DemandeResource
    {
        $this->autoriser($request, $demande);

        $this->service->accepter($demande, $request->user());

        return new DemandeResource($demande->fresh()->load(['reservation.espace.photos', 'demandeur']));
    }

    public function refuser(Request $request, DemandeModification $demande): DemandeResource
    {
        $this->autoriser($request, $demande);

        $data = $request->validate(['reponse' => ['nullable', 'string', 'max:500']]);
        $this->service->refuser($demande, $request->user(), $data['reponse'] ?? null);

        return new DemandeResource($demande->fresh()->load(['reservation.espace.photos', 'demandeur']));
    }

    private function autoriser(Request $request, DemandeModification $demande): void
    {
        $gerantId = $request->user()->gerant()->value('id');
        abort_unless($demande->reservation()->value('gerant_id') === $gerantId, 403);
    }
}
