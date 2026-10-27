<?php

namespace App\Services;

use App\Models\Abonnement;
use App\Models\Espace;
use App\Models\EspaceAbonnementFormule;
use App\Models\Parametre;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Réservations récurrentes / abonnement (TDR §8 — amélioration demandée par
 * les groupes qui répètent chaque semaine). Le gérant décide, par espace,
 * s'il propose cette formule et à quel prix forfaitaire (`EspaceAbonnementFormule`,
 * géré depuis EspaceGestion) : rien n'est calculé automatiquement à partir du
 * tarif horaire.
 *
 * Un abonnement porte le prix et le paiement unique de l'acompte ; chaque
 * séance reste une Reservation normale (même créneau hebdomadaire, répété
 * `nombre_seances` fois), rattachée via `abonnement_id`. Le cycle de vie
 * d'une séance (report, annulation, clôture, avis…) passe donc par
 * ReservationService sans rien dupliquer ici.
 */
class AbonnementService
{
    public function __construct(
        private readonly DisponibiliteService $dispo,
        private readonly NotificationService $notifications,
    ) {}

    public function creer(User $client, Espace $espace, EspaceAbonnementFormule $formule, CarbonImmutable $premierDebut, float $dureeHeures): Abonnement
    {
        $this->verifierDemande($espace, $formule, $premierDebut, $dureeHeures);

        $nombreSeances = $formule->nombre_seances;
        $dureeMinutes = (int) round($dureeHeures * 60);
        $occurrences = collect(range(0, $nombreSeances - 1))->map(fn (int $i) => $premierDebut->addWeeks($i));

        $tauxAcompte = (float) Parametre::valeur('deposit_rate', config('bookspace.deposit_rate'));
        $tauxCommission = (float) Parametre::valeur('commission_rate', config('bookspace.commission_rate'));

        $prixTotal = round((float) $formule->prix, 2);
        $montantAcompte = round($prixTotal * $tauxAcompte / 100, 2);
        $montantCommission = round($montantAcompte * $tauxCommission / 100, 2);
        $montantReverse = round($montantAcompte - $montantCommission, 2);

        return DB::transaction(function () use (
            $client, $espace, $formule, $occurrences, $dureeHeures, $dureeMinutes, $nombreSeances,
            $prixTotal, $tauxAcompte, $montantAcompte, $tauxCommission, $montantCommission, $montantReverse,
        ) {
            // Verrou : sérialise les créations concurrentes sur cet espace (comme ReservationService).
            Reservation::where('espace_id', $espace->id)
                ->whereIn('statut', ['en_attente', 'confirmee'])
                ->lockForUpdate()
                ->get();

            foreach ($occurrences as $debut) {
                $fin = $debut->addMinutes($dureeMinutes);

                if (! $this->dispo->estDisponible($espace, $debut, $fin)) {
                    throw ValidationException::withMessages([
                        'date_debut' => 'Le créneau du ' . date_longue($debut) . ' à ' . $debut->format('H:i')
                            . " n'est plus disponible : impossible de créer cet abonnement.",
                    ]);
                }
            }

            $abonnement = Abonnement::create([
                'reference' => $this->genererReference(),
                'client_id' => $client->id,
                'espace_id' => $espace->id,
                'gerant_id' => $espace->gerant_id,
                'formule_id' => $formule->id,
                'nombre_seances' => $nombreSeances,
                'duree_heures' => $dureeHeures,
                'prix_total' => $prixTotal,
                'taux_acompte' => $tauxAcompte,
                'montant_acompte' => $montantAcompte,
                'taux_commission' => $tauxCommission,
                'montant_commission' => $montantCommission,
                'montant_reverse_gerant' => $montantReverse,
                'statut' => 'en_attente',
            ]);

            // Répartition exacte du forfait entre les séances : chacune garde sa
            // propre part (ReversementService s'appuie sur les montants par
            // réservation, sans rien connaître des abonnements).
            $prixParSeance = $this->repartir($prixTotal, $nombreSeances);
            $acompteParSeance = $this->repartir($montantAcompte, $nombreSeances);
            $commissionParSeance = $this->repartir($montantCommission, $nombreSeances);
            $reverseParSeance = $this->repartir($montantReverse, $nombreSeances);

            foreach ($occurrences as $i => $debut) {
                Reservation::create([
                    'reference' => "{$abonnement->reference}-" . ($i + 1),
                    'client_id' => $client->id,
                    'espace_id' => $espace->id,
                    'gerant_id' => $espace->gerant_id,
                    'abonnement_id' => $abonnement->id,
                    'date_debut' => $debut,
                    'date_fin' => $debut->addMinutes($dureeMinutes),
                    'duree_heures' => $dureeHeures,
                    'prix_total' => $prixParSeance[$i],
                    'taux_acompte' => $tauxAcompte,
                    'montant_acompte' => $acompteParSeance[$i],
                    'taux_commission' => $tauxCommission,
                    'montant_commission' => $commissionParSeance[$i],
                    'montant_reverse_gerant' => $reverseParSeance[$i],
                    'statut' => 'en_attente',
                ]);
            }

            $this->notifications->abonnementCree($abonnement);

            return $abonnement;
        });
    }

    /**
     * Appelé quand l'acompte (unique, pour tout l'abonnement) est validé par
     * la passerelle Mobile Money : confirme l'abonnement et toutes ses
     * séances encore en attente d'un coup, sans notifier séance par séance.
     */
    public function confirmer(Abonnement $abonnement): Abonnement
    {
        if ($abonnement->statut !== 'en_attente') {
            return $abonnement;
        }

        DB::transaction(function () use ($abonnement) {
            $abonnement->update(['statut' => 'confirmee', 'confirmee_at' => now()]);

            $abonnement->reservations()->where('statut', 'en_attente')->get()->each(function (Reservation $r) {
                $r->update(['statut' => 'confirmee', 'confirmee_at' => now()]);
                $r->journaliser('confirmee', ['via' => 'abonnement']);
            });
        });

        $abonnement->journaliser('confirmee');
        $this->notifications->abonnementConfirme($abonnement);

        return $abonnement;
    }

    /** Annule l'abonnement et toutes ses séances à venir pas encore passées. */
    public function annuler(Abonnement $abonnement, User $acteur, ?string $motif = null): Abonnement
    {
        if (! $abonnement->estAnnulable()) {
            throw new RuntimeException("Cet abonnement ne peut plus être annulé.");
        }

        DB::transaction(function () use ($abonnement, $acteur, $motif) {
            $abonnement->update([
                'statut' => 'annulee',
                'annulee_par' => $acteur->id,
                'motif_annulation' => $motif,
                'annulee_at' => now(),
            ]);

            $abonnement->reservations()
                ->whereIn('statut', ['en_attente', 'confirmee'])
                ->where('date_debut', '>', now())
                ->get()
                ->each(function (Reservation $r) use ($acteur, $motif) {
                    $r->update([
                        'statut' => 'annulee',
                        'annulee_par' => $acteur->id,
                        'motif_annulation' => $motif,
                        'annulee_at' => now(),
                    ]);
                    $r->journaliser('annulee', ['via' => 'abonnement']);
                });
        });

        $abonnement->journaliser('annulee', ['par' => $acteur->id, 'motif' => $motif]);
        $this->notifications->abonnementAnnulee($abonnement);

        return $abonnement;
    }

    /**
     * Garde-fous indépendants de l'interface, comme ReservationService :
     * un composant Livewire peut recevoir n'importe quelle valeur.
     */
    private function verifierDemande(Espace $espace, EspaceAbonnementFormule $formule, CarbonImmutable $premierDebut, float $dureeHeures): void
    {
        if ($espace->statut !== 'valide') {
            throw ValidationException::withMessages(['date_debut' => "Cet espace n'est pas ouvert à la réservation."]);
        }

        if (! $espace->abonnement_actif) {
            throw ValidationException::withMessages(['formule' => "Cet espace ne propose pas d'abonnement."]);
        }

        if ($formule->espace_id !== $espace->id || ! $formule->actif) {
            throw ValidationException::withMessages(['formule' => 'Formule invalide.']);
        }

        if ($premierDebut->isPast()) {
            throw ValidationException::withMessages(['date_debut' => 'Le créneau doit être dans le futur.']);
        }

        if (
            $dureeHeures < ReservationService::DUREE_MIN
            || $dureeHeures > ReservationService::DUREE_MAX
            || fmod($dureeHeures * 2, 1.0) !== 0.0
        ) {
            throw ValidationException::withMessages([
                'duree_heures' => 'Durée invalide (de ' . ReservationService::DUREE_MIN . ' à ' . ReservationService::DUREE_MAX . ' h, par demi-heure).',
            ]);
        }
    }

    /**
     * Répartit un montant total en `$n` parts dont la somme est exactement
     * égale au total (le reliquat de l'arrondi va aux premières séances).
     *
     * @return array<int, float>
     */
    private function repartir(float $total, int $n): array
    {
        $totalCentimes = (int) round($total * 100);
        $base = intdiv($totalCentimes, $n);
        $reste = $totalCentimes - $base * $n;

        return collect(range(0, $n - 1))
            ->map(fn (int $i) => round(($base + ($i < $reste ? 1 : 0)) / 100, 2))
            ->all();
    }

    private function genererReference(): string
    {
        do {
            $ref = 'AB-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
        } while (Abonnement::where('reference', $ref)->exists());

        return $ref;
    }
}
