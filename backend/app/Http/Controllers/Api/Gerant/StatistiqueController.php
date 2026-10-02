<?php

namespace App\Http\Controllers\Api\Gerant;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Statistiques du gérant (TDR §5.2 : « taux d'occupation, revenus par période »).
 * Fournit aussi les séries nécessaires aux graphes du tableau de bord.
 */
class StatistiqueController extends Controller
{
    public function __invoke(Request $request)
    {
        $gerant = $request->user()->gerant()->firstOrFail();

        $data = $request->validate([
            'du' => ['nullable', 'date'],
            'au' => ['nullable', 'date', 'after_or_equal:du'],
        ]);

        $du = isset($data['du']) ? CarbonImmutable::parse($data['du'])->startOfDay() : CarbonImmutable::now()->startOfMonth();
        $au = isset($data['au']) ? CarbonImmutable::parse($data['au'])->endOfDay() : CarbonImmutable::now()->endOfMonth();

        $base = Reservation::where('gerant_id', $gerant->id)->whereBetween('date_debut', [$du, $au]);
        $confirmees = (clone $base)->whereIn('statut', ['confirmee', 'terminee']);

        $nbEspaces = $gerant->espaces()->where('statut', 'valide')->count();
        $heuresReservees = (float) (clone $confirmees)->sum('duree_heures');
        // Capacité indicative : 12 h ouvrées/jour/espace.
        $heuresDispo = max(1, $nbEspaces * ($du->diffInDays($au) + 1) * 12);

        return [
            'periode' => ['du' => $du->toDateString(), 'au' => $au->toDateString()],
            'reservations' => [
                'total' => (clone $base)->count(),
                'confirmees' => (clone $confirmees)->count(),
                'annulees' => (clone $base)->where('statut', 'annulee')->count(),
                'en_attente' => (clone $base)->where('statut', 'en_attente')->count(),
            ],
            'revenus' => [
                'chiffre_affaires' => round((float) (clone $confirmees)->sum('prix_total'), 2),
                'acomptes_encaisses' => round((float) (clone $confirmees)->sum('montant_acompte'), 2),
                'commissions_plateforme' => round((float) (clone $confirmees)->sum('montant_commission'), 2),
                'net_reverse' => round((float) (clone $confirmees)->sum('montant_reverse_gerant'), 2),
            ],
            'heures_reservees' => round($heuresReservees, 1),
            'taux_occupation' => round($heuresReservees / $heuresDispo * 100, 1),

            // Série journalière du chiffre d'affaires (graphe d'évolution).
            'serie' => $this->serieJournaliere($confirmees, $du, $au),

            // Répartition des réservations par statut (graphe en anneau).
            'repartition_statuts' => $this->repartition(clone $base),

            'par_espace' => (clone $confirmees)
                ->select('espace_id', DB::raw('count(*) as reservations'), DB::raw('sum(prix_total) as ca'))
                ->groupBy('espace_id')
                ->with('espace:id,nom')
                ->orderByDesc('ca')
                ->get()
                ->map(fn ($r) => [
                    'espace' => $r->espace?->nom,
                    'reservations' => (int) $r->reservations,
                    'chiffre_affaires' => round((float) $r->ca, 2),
                ]),
        ];
    }

    private function serieJournaliere($query, CarbonImmutable $du, CarbonImmutable $au): array
    {
        $parJour = (clone $query)
            ->select(DB::raw('DATE(date_debut) as jour'), DB::raw('sum(prix_total) as ca'), DB::raw('count(*) as n'))
            ->groupBy('jour')
            ->pluck('ca', 'jour');

        $compteJour = (clone $query)
            ->select(DB::raw('DATE(date_debut) as jour'), DB::raw('count(*) as n'))
            ->groupBy('jour')
            ->pluck('n', 'jour');

        $serie = [];
        for ($j = $du; $j->lessThanOrEqualTo($au); $j = $j->addDay()) {
            $cle = $j->toDateString();
            $serie[] = [
                'date' => $cle,
                'chiffre_affaires' => round((float) ($parJour[$cle] ?? 0), 2),
                'reservations' => (int) ($compteJour[$cle] ?? 0),
            ];
        }

        return $serie;
    }

    private function repartition($query): array
    {
        $counts = (clone $query)
            ->select('statut', DB::raw('count(*) as n'))
            ->groupBy('statut')
            ->pluck('n', 'statut');

        return [
            'confirmee' => (int) ($counts['confirmee'] ?? 0),
            'terminee' => (int) ($counts['terminee'] ?? 0),
            'en_attente' => (int) ($counts['en_attente'] ?? 0),
            'annulee' => (int) ($counts['annulee'] ?? 0),
        ];
    }
}
