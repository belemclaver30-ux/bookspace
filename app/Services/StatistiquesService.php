<?php

namespace App\Services;

use App\Models\Espace;
use App\Models\Gerant;
use App\Models\Litige;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Indicateurs et séries des tableaux de bord (TDR §5.2 gérant : « taux
 * d'occupation, revenus par période » ; §5.3 admin : supervision plateforme).
 */
class StatistiquesService
{
    /** Statistiques d'un gérant sur une période (mois courant par défaut). */
    public function gerant(Gerant $gerant, ?string $du = null, ?string $au = null): array
    {
        $du = $du ? CarbonImmutable::parse($du)->startOfDay() : CarbonImmutable::now()->startOfMonth();
        $au = $au ? CarbonImmutable::parse($au)->endOfDay() : CarbonImmutable::now()->endOfMonth();

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

    /** Vue d'ensemble de la plateforme pour l'administrateur. */
    public function plateforme(): array
    {
        $confirmees = Reservation::whereIn('statut', ['confirmee', 'terminee']);

        return [
            'utilisateurs' => [
                'clients' => User::where('role', 'client')->count(),
                'gerants' => User::where('role', 'gerant')->count(),
            ],
            'gerants_en_attente' => Gerant::where('statut_validation', 'en_attente')->count(),
            'espaces' => [
                'valides' => Espace::where('statut', 'valide')->count(),
                'en_attente' => Espace::where('statut', 'en_attente')->count(),
            ],
            'reservations' => [
                'total' => Reservation::count(),
                'confirmees' => (clone $confirmees)->count(),
            ],
            'commissions_percues' => round((float) (clone $confirmees)->sum('montant_commission'), 2),
            'litiges_ouverts' => Litige::whereIn('statut', ['ouvert', 'en_cours'])->count(),

            // Commissions perçues, mois par mois sur 6 mois (graphe d'évolution).
            'commissions_par_mois' => $this->commissionsParMois(),

            // Répartition des espaces par type (graphe en anneau).
            'espaces_par_type' => [
                'salle_repetition' => Espace::where('type', 'salle_repetition')->count(),
                'terrain_foot' => Espace::where('type', 'terrain_foot')->count(),
            ],

            // Répartition des réservations par statut.
            'reservations_par_statut' => Reservation::select('statut', DB::raw('count(*) as n'))
                ->groupBy('statut')->pluck('n', 'statut'),

            // Top 5 des espaces par chiffre d'affaires (graphe en barres).
            'top_espaces' => (clone $confirmees)
                ->select('espace_id', DB::raw('sum(prix_total) as ca'))
                ->groupBy('espace_id')->orderByDesc('ca')->limit(5)
                ->with('espace:id,nom')->get()
                ->map(fn ($r) => ['espace' => $r->espace?->nom, 'chiffre_affaires' => round((float) $r->ca, 2)]),
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

    private function commissionsParMois(): array
    {
        $debut = CarbonImmutable::now()->startOfMonth()->subMonths(5);

        // Regroupement en PHP pour rester indépendant du SGBD (MySQL / SQLite).
        $parMois = Reservation::whereIn('statut', ['confirmee', 'terminee'])
            ->where('date_debut', '>=', $debut)
            ->get(['date_debut', 'montant_commission'])
            ->groupBy(fn ($r) => $r->date_debut->format('Y-m'))
            ->map(fn ($groupe) => (float) $groupe->sum('montant_commission'));

        $serie = [];
        for ($m = $debut; $m->lessThanOrEqualTo(CarbonImmutable::now()); $m = $m->addMonth()) {
            $cle = $m->format('Y-m');
            $serie[] = ['mois' => $cle, 'commissions' => round($parMois[$cle] ?? 0, 2)];
        }

        return $serie;
    }
}
