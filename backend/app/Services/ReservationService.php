<?php

namespace App\Services;

use App\Models\Espace;
use App\Models\Parametre;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Création et cycle de vie d'une réservation (TDR §8).
 * La création verrouille les réservations concurrentes du même espace le temps
 * de re-vérifier la disponibilité dans la transaction : c'est la garantie
 * « aucune double réservation possible sur un même créneau » (critère §15).
 */
class ReservationService
{
    public function __construct(
        private readonly DisponibiliteService $dispo,
        private readonly NotificationService $notifications,
    ) {}

    public function creer(User $client, Espace $espace, CarbonImmutable $debut, float $dureeHeures): Reservation
    {
        $fin = $debut->addMinutes((int) round($dureeHeures * 60));

        $tauxAcompte = (float) Parametre::valeur('deposit_rate', config('bookspace.deposit_rate'));
        $tauxCommission = (float) Parametre::valeur('commission_rate', config('bookspace.commission_rate'));

        $prixTotal = round((float) $espace->tarif_horaire * $dureeHeures, 2);
        $montantAcompte = round($prixTotal * $tauxAcompte / 100, 2);
        $montantCommission = round($montantAcompte * $tauxCommission / 100, 2);

        return DB::transaction(function () use (
            $client, $espace, $debut, $fin, $dureeHeures,
            $prixTotal, $tauxAcompte, $montantAcompte, $tauxCommission, $montantCommission
        ) {
            // Verrou : sérialise les créations concurrentes sur cet espace.
            Reservation::where('espace_id', $espace->id)
                ->whereIn('statut', ['en_attente', 'confirmee'])
                ->lockForUpdate()
                ->get();

            if (! $this->dispo->estDisponible($espace, $debut, $fin)) {
                throw ValidationException::withMessages([
                    'date_debut' => 'Ce créneau n\'est plus disponible.',
                ]);
            }

            $reservation = Reservation::create([
                'reference' => $this->genererReference(),
                'client_id' => $client->id,
                'espace_id' => $espace->id,
                'gerant_id' => $espace->gerant_id,
                'date_debut' => $debut,
                'date_fin' => $fin,
                'duree_heures' => $dureeHeures,
                'prix_total' => $prixTotal,
                'taux_acompte' => $tauxAcompte,
                'montant_acompte' => $montantAcompte,
                'taux_commission' => $tauxCommission,
                'montant_commission' => $montantCommission,
                'montant_reverse_gerant' => round($montantAcompte - $montantCommission, 2),
                'statut' => 'en_attente',
            ]);

            $this->notifications->reservationCreee($reservation);

            return $reservation;
        });
    }

    /**
     * Création manuelle par le gérant (client au comptoir/téléphone, sans
     * compte BookSpace) : réservation confirmée directement, pas de paiement
     * en ligne donc pas de commission plateforme prélevée (encaissée hors app).
     */
    public function creerManuelle(
        Espace $espace,
        CarbonImmutable $debut,
        float $dureeHeures,
        string $clientNom,
        string $clientTelephone,
        User $acteur,
    ): Reservation {
        $fin = $debut->addMinutes((int) round($dureeHeures * 60));
        $prixTotal = round((float) $espace->tarif_horaire * $dureeHeures, 2);

        return DB::transaction(function () use ($espace, $debut, $fin, $dureeHeures, $prixTotal, $clientNom, $clientTelephone, $acteur) {
            // Verrou : sérialise les créations concurrentes sur cet espace.
            Reservation::where('espace_id', $espace->id)
                ->whereIn('statut', ['en_attente', 'confirmee'])
                ->lockForUpdate()
                ->get();

            if (! $this->dispo->estDisponible($espace, $debut, $fin)) {
                throw ValidationException::withMessages([
                    'date_debut' => 'Ce créneau n\'est plus disponible.',
                ]);
            }

            $reservation = Reservation::create([
                'reference' => $this->genererReference(),
                'client_id' => null,
                'client_nom' => $clientNom,
                'client_telephone' => $clientTelephone,
                'origine' => 'gerant',
                'espace_id' => $espace->id,
                'gerant_id' => $espace->gerant_id,
                'date_debut' => $debut,
                'date_fin' => $fin,
                'duree_heures' => $dureeHeures,
                'prix_total' => $prixTotal,
                'taux_acompte' => 100,
                'montant_acompte' => $prixTotal,
                'taux_commission' => 0,
                'montant_commission' => 0,
                'montant_reverse_gerant' => $prixTotal,
                'statut' => 'confirmee',
                'confirmee_at' => now(),
            ]);

            $reservation->journaliser('creee_manuellement', ['par' => $acteur->id]);

            return $reservation;
        });
    }

    /** Appelé quand l'acompte est validé par la passerelle Mobile Money. */
    public function confirmer(Reservation $reservation): Reservation
    {
        if ($reservation->statut !== 'en_attente') {
            return $reservation;
        }

        $reservation->update([
            'statut' => 'confirmee',
            'confirmee_at' => now(),
        ]);
        $reservation->journaliser('confirmee');

        $this->notifications->reservationConfirmee($reservation);

        return $reservation;
    }

    /**
     * Reporte (déplace) une réservation vers un nouveau créneau. La durée est
     * conservée sauf si `$dureeHeures` est fourni ; le prix est recalculé si la
     * durée change. Vérifie la disponibilité en ignorant la réservation
     * elle-même (verrou transactionnel comme à la création).
     */
    public function reporter(Reservation $reservation, User $acteur, CarbonImmutable $nouveauDebut, ?float $dureeHeures = null): Reservation
    {
        if (! $reservation->estReportable()) {
            throw ValidationException::withMessages([
                'date_debut' => "Cette réservation ne peut plus être déplacée.",
            ]);
        }

        $duree = $dureeHeures ?? (float) $reservation->duree_heures;
        $nouvelleFin = $nouveauDebut->addMinutes((int) round($duree * 60));
        $ancienDebut = $reservation->date_debut->copy();

        $espace = $reservation->espace()->firstOrFail();

        return DB::transaction(function () use ($reservation, $acteur, $espace, $nouveauDebut, $nouvelleFin, $duree, $ancienDebut) {
            Reservation::where('espace_id', $espace->id)
                ->whereIn('statut', ['en_attente', 'confirmee'])
                ->lockForUpdate()
                ->get();

            if (! $this->dispo->estDisponible($espace, $nouveauDebut, $nouvelleFin, $reservation->id)) {
                throw ValidationException::withMessages([
                    'date_debut' => "Ce créneau n'est pas disponible.",
                ]);
            }

            $maj = [
                'date_debut' => $nouveauDebut,
                'date_fin' => $nouvelleFin,
                'duree_heures' => $duree,
            ];

            // Recalcul du prix si la durée a changé.
            if ((float) $reservation->duree_heures !== $duree) {
                $prixTotal = round((float) $espace->tarif_horaire * $duree, 2);
                $montantAcompte = round($prixTotal * (float) $reservation->taux_acompte / 100, 2);
                $montantCommission = round($montantAcompte * (float) $reservation->taux_commission / 100, 2);
                $maj += [
                    'prix_total' => $prixTotal,
                    'montant_acompte' => $montantAcompte,
                    'montant_commission' => $montantCommission,
                    'montant_reverse_gerant' => round($montantAcompte - $montantCommission, 2),
                ];
            }

            $reservation->update($maj);
            $reservation->journaliser('reportee', [
                'par' => $acteur->id,
                'ancien_debut' => $ancienDebut->toIso8601String(),
                'nouveau_debut' => $nouveauDebut->toIso8601String(),
            ]);

            $this->notifications->reservationReportee($reservation, $ancienDebut);

            return $reservation;
        });
    }

    public function annuler(Reservation $reservation, User $acteur, ?string $motif = null): Reservation
    {
        if (! $reservation->estAnnulable()) {
            throw new RuntimeException("Cette réservation ne peut plus être annulée.");
        }

        $reservation->update([
            'statut' => 'annulee',
            'annulee_par' => $acteur->id,
            'motif_annulation' => $motif,
            'annulee_at' => now(),
        ]);
        $reservation->journaliser('annulee', ['par' => $acteur->id, 'motif' => $motif]);

        $this->notifications->reservationAnnulee($reservation);

        return $reservation;
    }

    private function genererReference(): string
    {
        do {
            $ref = 'BK-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
        } while (Reservation::where('reference', $ref)->exists());

        return $ref;
    }
}
