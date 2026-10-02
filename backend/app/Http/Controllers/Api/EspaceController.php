<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EspaceResource;
use App\Models\Espace;
use App\Services\DisponibiliteService;
use App\Services\GeoService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Recherche et consultation des espaces (TDR §5.1 : « rechercher un espace par
 * ville, type, date et créneau » / « consulter la fiche d'un espace »).
 */
class EspaceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtres = $request->validate([
            'ville' => ['nullable', 'string'],
            'type' => ['nullable', Rule::in(['salle_repetition', 'terrain_foot'])],
            'q' => ['nullable', 'string'],
            'prix_max' => ['nullable', 'numeric', 'min:0'],
            'tri' => ['nullable', Rule::in(['note', 'prix_asc', 'prix_desc', 'recent', 'proche'])],
            // Position du client pour le tri / le filtre par proximité.
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'rayon_km' => ['nullable', 'numeric', 'min:1', 'max:500'],
        ]);

        $base = Espace::query()
            ->publies()
            ->with(['photos', 'gerant'])
            ->recherche($filtres['ville'] ?? null, $filtres['type'] ?? null, $filtres['q'] ?? null)
            ->when($filtres['prix_max'] ?? null, fn ($q, $p) => $q->where('tarif_horaire', '<=', $p));

        $position = isset($filtres['lat']) ? [(float) $filtres['lat'], (float) $filtres['lng']] : null;

        // Mode proximité : calcul de distance en PHP (portable MySQL/SQLite),
        // tri par distance croissante, filtre optionnel par rayon.
        if ($position && (($filtres['tri'] ?? null) === 'proche' || isset($filtres['rayon_km']))) {
            $espaces = $base->whereNotNull('latitude')->whereNotNull('longitude')->get()
                ->map(function (Espace $e) use ($position) {
                    $e->distance_km = GeoService::distanceKm($position[0], $position[1], (float) $e->latitude, (float) $e->longitude);

                    return $e;
                })
                ->when(isset($filtres['rayon_km']), fn ($c) => $c->filter(fn ($e) => $e->distance_km <= $filtres['rayon_km']))
                ->sortBy('distance_km')
                ->values();

            return EspaceResource::collection($espaces);
        }

        $espaces = $base
            ->when($filtres['tri'] ?? null, function ($q, $tri) {
                match ($tri) {
                    'note' => $q->orderByDesc('note_moyenne'),
                    'prix_asc' => $q->orderBy('tarif_horaire'),
                    'prix_desc' => $q->orderByDesc('tarif_horaire'),
                    'recent' => $q->latest(),
                    default => null,
                };
            }, fn ($q) => $q->orderByDesc('mis_en_avant')->orderByDesc('note_moyenne'))
            ->paginate(15)
            ->withQueryString();

        // Ajoute la distance à chaque résultat si la position est connue.
        if ($position) {
            $espaces->getCollection()->transform(function (Espace $e) use ($position) {
                if ($e->latitude !== null && $e->longitude !== null) {
                    $e->distance_km = GeoService::distanceKm($position[0], $position[1], (float) $e->latitude, (float) $e->longitude);
                }

                return $e;
            });
        }

        return EspaceResource::collection($espaces);
    }

    public function show(Espace $espace): EspaceResource
    {
        abort_unless($espace->statut === 'valide', 404);

        $espace->load([
            'photos',
            'gerant',
            'disponibilites',
            'evaluations' => fn ($q) => $q->with('client')->latest()->limit(20),
        ]);

        return new EspaceResource($espace);
    }

    /** Créneaux horaires d'une journée pour l'écran « Choisir un créneau ». */
    public function creneaux(Request $request, Espace $espace, DisponibiliteService $dispo): array
    {
        abort_unless($espace->statut === 'valide', 404);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'duree' => ['nullable', 'numeric', 'min:1', 'max:12'],
        ]);
        $jour = CarbonImmutable::parse($data['date'])->startOfDay();
        $duree = (float) ($data['duree'] ?? 1);

        return [
            'date' => $jour->toDateString(),
            'duree' => $duree,
            'creneaux' => $dispo->creneauxDuJour($espace, $jour, $duree),
        ];
    }
}
