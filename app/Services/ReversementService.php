<?php

namespace App\Services;

use App\Models\Gerant;
use App\Models\Reservation;
use App\Models\Reversement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Reversement aux gérants de leur part des acomptes encaissés en ligne
 * (acompte − commission, figé sur chaque réservation).
 *
 * - « À reverser » : réservations en ligne terminées, pas encore reversées.
 *   On attend la fin du créneau pour ne pas reverser un acompte qui pourrait
 *   encore faire l'objet d'une annulation ou d'un litige.
 * - « À venir » : réservations en ligne confirmées dont le créneau n'est pas passé.
 * - Les réservations saisies à la main par le gérant sont encaissées sur place :
 *   BookSpace ne leur doit rien.
 *
 * Le virement Mobile Money lui-même est fait par l'administrateur (hors
 * application) ; il l'enregistre ensuite ici avec la référence de la transaction.
 */
class ReversementService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /** Réservations dont la part revient au gérant via BookSpace. */
    public function enLigne(): Builder
    {
        return Reservation::query()
            ->where('origine', '!=', 'gerant')
            ->where('montant_reverse_gerant', '>', 0);
    }

    public function aReverser(Gerant|int $gerant): Builder
    {
        return $this->enLigne()
            ->where('gerant_id', $gerant instanceof Gerant ? $gerant->id : $gerant)
            ->where('statut', 'terminee')
            ->whereNull('reversement_id');
    }

    /**
     * Soldes par gérant, en trois requêtes groupées.
     *
     * @return array<int, array{du: float, nb_du: int, a_venir: float, reverse: float}>
     */
    public function soldes(): array
    {
        $soldes = [];
        $ajouter = function (string $cle, $lignes, ?string $compte = null) use (&$soldes) {
            foreach ($lignes as $l) {
                $soldes[$l->gerant_id] ??= ['du' => 0.0, 'nb_du' => 0, 'a_venir' => 0.0, 'reverse' => 0.0];
                $soldes[$l->gerant_id][$cle] = round((float) $l->total, 2);
                if ($compte) {
                    $soldes[$l->gerant_id][$compte] = (int) $l->nb;
                }
            }
        };

        $somme = 'gerant_id, SUM(montant_reverse_gerant) as total, COUNT(*) as nb';

        $ajouter('du', $this->enLigne()->where('statut', 'terminee')->whereNull('reversement_id')
            ->selectRaw($somme)->groupBy('gerant_id')->get(), 'nb_du');
        $ajouter('a_venir', $this->enLigne()->where('statut', 'confirmee')
            ->selectRaw($somme)->groupBy('gerant_id')->get());
        $ajouter('reverse', Reversement::selectRaw('gerant_id, SUM(montant) as total')->groupBy('gerant_id')->get());

        return $soldes;
    }

    /**
     * Enregistre le versement de tout ce qui est dû au gérant. Les réservations
     * sont verrouillées : deux administrateurs ne peuvent pas reverser deux fois.
     */
    public function enregistrer(Gerant $gerant, string $referenceOperateur, ?string $note, User $admin): Reversement
    {
        if (! $gerant->peutRecevoirVersement()) {
            throw new RuntimeException("Ce gérant n'a pas renseigné de compte Mobile Money.");
        }

        $reversement = DB::transaction(function () use ($gerant, $referenceOperateur, $note, $admin) {
            $reservations = $this->aReverser($gerant)->lockForUpdate()->get(['id', 'montant_reverse_gerant']);

            if ($reservations->isEmpty()) {
                throw new RuntimeException('Rien à reverser à ce gérant.');
            }

            $reversement = $gerant->reversements()->create([
                'montant' => round((float) $reservations->sum('montant_reverse_gerant'), 2),
                'nb_reservations' => $reservations->count(),
                'momo_operateur' => $gerant->momo_operateur,
                'momo_numero' => $gerant->momo_numero,
                'momo_titulaire' => $gerant->momo_titulaire,
                'reference_operateur' => $referenceOperateur,
                'note' => $note,
                'effectue_par' => $admin->id,
            ]);

            Reservation::whereKey($reservations->modelKeys())->update(['reversement_id' => $reversement->id]);

            return $reversement;
        });

        $this->notifications->reversementEffectue($reversement);

        return $reversement;
    }
}
