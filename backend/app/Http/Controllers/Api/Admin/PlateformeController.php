<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Espace;
use App\Models\Gerant;
use App\Models\Litige;
use App\Models\Parametre;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Supervision de la plateforme + paramètres (commission) + litiges (TDR §5.3).
 */
class PlateformeController extends Controller
{
    public function tableauDeBord()
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

    public function parametres()
    {
        return Parametre::all();
    }

    public function majParametre(Request $request, string $cle)
    {
        $data = $request->validate(['valeur' => ['required', 'string']]);

        abort_unless(Parametre::query()->whereKey($cle)->exists(), 404);
        Parametre::definir($cle, $data['valeur']);

        return response()->json(['data' => Parametre::find($cle)]);
    }

    public function litiges(Request $request)
    {
        return Litige::with(['reservation', 'auteur'])
            ->when($request->query('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->latest()
            ->paginate(20);
    }

    public function resoudreLitige(Request $request, Litige $litige)
    {
        $data = $request->validate([
            'statut' => ['required', 'in:en_cours,resolu,ferme'],
            'resolution' => ['nullable', 'string', 'max:2000'],
        ]);

        $litige->update($data + [
            'resolu_par' => $request->user()->id,
            'resolu_at' => in_array($data['statut'], ['resolu', 'ferme'], true) ? now() : null,
        ]);

        return response()->json(['data' => $litige->fresh(['reservation', 'auteur', 'resolveur'])]);
    }
}
