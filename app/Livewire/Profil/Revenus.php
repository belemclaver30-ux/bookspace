<?php

namespace App\Livewire\Profil;

use App\Models\Gerant;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Profil > Mes revenus (gérant) : relevé mois par mois sur les 12 derniers
 * mois, en complément du tableau de bord qui couvre le mois en cours.
 *
 * - Réservation en ligne : le client paie l'acompte à BookSpace, qui le reverse
 *   au gérant après commission ; le solde est payé sur place.
 * - Réservation ajoutée manuellement : tout est encaissé sur place, sans commission.
 */
#[Layout('components.layouts.dashboard')]
#[Title('Mes revenus')]
class Revenus extends Component
{
    private const MOIS = 12;

    public function render()
    {
        $gerant = auth()->user()->gerant()->firstOrFail();
        $debut = CarbonImmutable::now()->startOfMonth()->subMonths(self::MOIS - 1);

        // Regroupement en PHP pour rester indépendant du SGBD (MySQL / SQLite).
        $parMois = Reservation::where('gerant_id', $gerant->id)
            ->whereIn('statut', ['confirmee', 'terminee'])
            ->where('date_debut', '>=', $debut)
            ->get(['date_debut', 'origine', 'prix_total', 'montant_acompte', 'montant_commission', 'montant_reverse_gerant'])
            ->groupBy(fn (Reservation $r) => $r->date_debut->format('Y-m'));

        $mois = [];
        for ($m = CarbonImmutable::now()->startOfMonth(); $m->greaterThanOrEqualTo($debut); $m = $m->subMonth()) {
            $mois[] = $this->releve($m, $parMois[$m->format('Y-m')] ?? collect());
        }

        return view('livewire.profil.revenus', [
            'gerant' => $gerant,
            'mois' => $mois,
            'total' => [
                'reservations' => array_sum(array_column($mois, 'reservations')),
                'chiffre_affaires' => array_sum(array_column($mois, 'chiffre_affaires')),
                'commission' => array_sum(array_column($mois, 'commission')),
                'verse' => array_sum(array_column($mois, 'verse')),
                'sur_place' => array_sum(array_column($mois, 'sur_place')),
            ],
            'operateur' => Gerant::OPERATEURS[$gerant->momo_operateur] ?? null,
        ]);
    }

    private function releve(CarbonImmutable $mois, $reservations): array
    {
        $enLigne = $reservations->where('origine', '!=', 'gerant');

        return [
            'mois' => $mois,
            'reservations' => $reservations->count(),
            'chiffre_affaires' => (float) $reservations->sum('prix_total'),
            'acomptes' => (float) $enLigne->sum('montant_acompte'),
            'commission' => (float) $enLigne->sum('montant_commission'),
            // Part des acomptes en ligne reversée par BookSpace.
            'verse' => (float) $enLigne->sum('montant_reverse_gerant'),
            // Soldes des réservations en ligne + réservations manuelles.
            'sur_place' => (float) $reservations->sum('prix_total') - (float) $enLigne->sum('montant_acompte'),
        ];
    }
}
