<?php

namespace App\Services;

use App\Models\Abonnement;
use App\Models\Litige;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Services\MobileMoney\PasserelleManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Paiement de l'acompte via Mobile Money (TDR §8, §10.3).
 *
 * - `initier()` : crée le paiement et déclenche la demande auprès de la
 *   passerelle. Avec le driver `fake`, le paiement est confirmé dans la foulée.
 * - `initierAbonnement()` : même principe, mais pour l'acompte unique d'un
 *   abonnement (TDR §8 — amélioration) ; rattaché à sa première séance.
 * - `confirmerCallback()` : appelé par le webhook opérateur (ou la simulation).
 */
class PaiementService
{
    public function __construct(
        private readonly ReservationService $reservations,
        private readonly AbonnementService $abonnements,
        private readonly PasserelleManager $passerelles,
    ) {}

    public function initier(Reservation $reservation, string $mode, string $telephone): Paiement
    {
        // On sérialise sur la ligne réservation : deux requêtes concurrentes
        // (double-tap, retour navigateur) ne peuvent pas lancer deux paiements.
        $paiement = DB::transaction(function () use ($reservation, $mode, $telephone) {
            $verrou = Reservation::whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();

            if ($verrou->statut !== 'en_attente') {
                throw ValidationException::withMessages([
                    'reservation' => "Cette réservation n'attend pas de paiement.",
                ]);
            }

            // Un paiement déjà en cours ou validé : on renvoie l'existant plutôt
            // que d'en créer un second (et de solliciter le client deux fois).
            $existant = $verrou->paiements()
                ->whereIn('statut', ['en_cours', 'valide'])
                ->latest()
                ->first();

            if ($existant) {
                return $existant;
            }

            return Paiement::create([
                'reservation_id' => $verrou->id,
                'montant' => $verrou->montant_acompte,
                'mode' => $mode,
                'telephone' => $telephone,
                'statut' => 'initie',
                'payload' => ['driver' => config('bookspace.mobile_money.driver'), 'initie_at' => now()->toIso8601String()],
            ]);
        });

        // Paiement déjà en cours/validé récupéré : rien de plus à déclencher.
        if (in_array($paiement->statut, ['en_cours', 'valide'], true)) {
            return $paiement;
        }

        $passerelle = $this->passerelles->pour($mode);
        $demande = $passerelle->demanderPaiement($paiement->load('reservation'));

        $paiement->update([
            'reference_operateur' => $demande['reference'],
            'statut' => 'en_cours',
            'payload' => array_merge($paiement->payload ?? [], ['demande' => $demande]),
        ]);
        $paiement->journaliser('initie');

        // Driver fake : la passerelle valide immédiatement.
        if (($demande['statut'] ?? null) === 'valide') {
            $this->confirmerCallback($paiement->reference_operateur, true, ['source' => 'auto']);
            $paiement->refresh();
        }

        return $paiement;
    }

    /**
     * Acompte unique d'un abonnement (toutes ses séances d'un coup) : le
     * paiement est rattaché à la première séance, seul point d'entrée que
     * connaissent les drivers Mobile Money (`Paiement belongsTo Reservation`).
     */
    public function initierAbonnement(Abonnement $abonnement, string $mode, string $telephone): Paiement
    {
        $paiement = DB::transaction(function () use ($abonnement, $mode, $telephone) {
            $verrou = Abonnement::whereKey($abonnement->getKey())->lockForUpdate()->firstOrFail();

            if ($verrou->statut !== 'en_attente') {
                throw ValidationException::withMessages([
                    'abonnement' => "Cet abonnement n'attend pas de paiement.",
                ]);
            }

            $premiereSeance = $verrou->reservations()->oldest('date_debut')->first();

            if (! $premiereSeance) {
                throw new RuntimeException('Abonnement sans aucune séance.');
            }

            $existant = $premiereSeance->paiements()
                ->whereIn('statut', ['en_cours', 'valide'])
                ->latest()
                ->first();

            if ($existant) {
                return $existant;
            }

            return Paiement::create([
                'reservation_id' => $premiereSeance->id,
                'montant' => $verrou->montant_acompte,
                'mode' => $mode,
                'telephone' => $telephone,
                'statut' => 'initie',
                'payload' => ['driver' => config('bookspace.mobile_money.driver'), 'initie_at' => now()->toIso8601String()],
            ]);
        });

        if (in_array($paiement->statut, ['en_cours', 'valide'], true)) {
            return $paiement;
        }

        $passerelle = $this->passerelles->pour($mode);
        $demande = $passerelle->demanderPaiement($paiement->load('reservation'));

        $paiement->update([
            'reference_operateur' => $demande['reference'],
            'statut' => 'en_cours',
            'payload' => array_merge($paiement->payload ?? [], ['demande' => $demande]),
        ]);
        $paiement->journaliser('initie');

        if (($demande['statut'] ?? null) === 'valide') {
            $this->confirmerCallback($paiement->reference_operateur, true, ['source' => 'auto']);
            $paiement->refresh();
        }

        return $paiement;
    }

    public function confirmerCallback(string $referenceOperateur, bool $succes, array $payload = []): ?Paiement
    {
        $paiement = Paiement::where('reference_operateur', $referenceOperateur)->first();

        if (! $paiement || $paiement->statut === 'valide') {
            return $paiement;
        }

        $paiement->update([
            'statut' => $succes ? 'valide' : 'echoue',
            'paye_at' => $succes ? now() : null,
            'payload' => array_merge($paiement->payload ?? [], ['callback' => $payload, 'recu_at' => now()->toIso8601String()]),
        ]);
        $paiement->journaliser($succes ? 'paiement_valide' : 'paiement_echoue');

        if ($succes) {
            $reservation = $paiement->reservation;

            if ($reservation->abonnement_id) {
                $abonnement = $this->abonnements->confirmer($reservation->abonnement);

                // Argent reçu pour un abonnement déjà annulé : les créneaux ont pu
                // être repris, on ne les réactive pas. Un litige est ouvert pour
                // que l'administrateur rembourse le client.
                if ($abonnement->statut === 'annulee') {
                    Litige::create([
                        'reservation_id' => $reservation->id,
                        'ouvert_par' => $abonnement->client_id,
                        'sujet' => "Acompte reçu après l'annulation de l'abonnement {$abonnement->reference}",
                        'description' => 'Paiement de ' . fcfa($paiement->montant) . " confirmé par l'opérateur "
                            . "(référence {$paiement->reference_operateur}) alors que l'abonnement était déjà annulé. "
                            . 'À rembourser au client. — Signalement automatique.',
                    ]);
                    $paiement->journaliser('paiement_apres_annulation');
                }
            } else {
                $reservation = $this->reservations->confirmer($reservation);

                // Argent reçu pour une réservation déjà expirée ou annulée : le
                // créneau a pu être repris, on ne la réactive pas. Un litige est
                // ouvert pour que l'administrateur rembourse le client.
                if ($reservation->statut === 'annulee' && $reservation->client_id) {
                    Litige::create([
                        'reservation_id' => $reservation->id,
                        'ouvert_par' => $reservation->client_id,
                        'sujet' => "Acompte reçu après l'annulation de {$reservation->reference}",
                        'description' => 'Paiement de ' . fcfa($paiement->montant) . " confirmé par l'opérateur "
                            . "(référence {$paiement->reference_operateur}) alors que la réservation était déjà annulée. "
                            . 'À rembourser au client. — Signalement automatique.',
                    ]);
                    $paiement->journaliser('paiement_apres_annulation');
                }
            }
        }

        return $paiement;
    }

    /** Re-synchronise l'état d'un paiement en_cours auprès de l'opérateur. */
    public function synchroniser(Paiement $paiement): Paiement
    {
        if ($paiement->statut !== 'en_cours' || ! $paiement->reference_operateur) {
            return $paiement;
        }

        $statut = $this->passerelles->pour($paiement->mode)->verifierStatut($paiement->reference_operateur);

        if ($statut !== 'en_cours') {
            $this->confirmerCallback($paiement->reference_operateur, $statut === 'valide', ['source' => 'polling']);
            $paiement->refresh();
        }

        return $paiement;
    }
}
