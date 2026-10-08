<?php

namespace App\Services;

use App\Models\DemandeModification;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Cycle de vie des demandes client (annulation / report) validées par le gérant.
 */
class DemandeService
{
    public function __construct(
        private readonly ReservationService $reservations,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Le client soumet une demande sur une réservation confirmée à venir.
     */
    public function creer(
        Reservation $reservation,
        User $client,
        string $type,
        ?string $motif,
        ?CarbonImmutable $nouveauDebut = null,
        ?float $nouvelleDuree = null,
    ): DemandeModification {
        if ($reservation->statut !== 'confirmee') {
            throw ValidationException::withMessages([
                'reservation' => $reservation->statut === 'en_attente'
                    ? "Cette réservation n'est pas encore confirmée : vous pouvez l'annuler directement."
                    : "Cette réservation ne peut plus être modifiée.",
            ]);
        }

        if (! $reservation->date_debut->isFuture()) {
            throw ValidationException::withMessages(['reservation' => 'Le créneau est déjà passé.']);
        }

        if ($reservation->demandeEnCours()->exists()) {
            throw ValidationException::withMessages(['reservation' => 'Une demande est déjà en attente de traitement.']);
        }

        if ($type === 'report' && ! $nouveauDebut) {
            throw ValidationException::withMessages(['date_debut' => 'Proposez une nouvelle date pour le report.']);
        }

        $demande = DemandeModification::create([
            'reservation_id' => $reservation->id,
            'demandeur_id' => $client->id,
            'type' => $type,
            'statut' => 'en_attente',
            'motif' => $motif,
            'nouvelle_date_debut' => $type === 'report' ? $nouveauDebut : null,
            'nouvelle_duree_heures' => $type === 'report' ? ($nouvelleDuree ?? $reservation->duree_heures) : null,
        ]);

        $this->notifications->demandeRecue($demande);

        return $demande;
    }

    /** Le client retire sa demande tant qu'elle est en attente. */
    public function retirer(DemandeModification $demande): DemandeModification
    {
        if (! $demande->estEnAttente()) {
            throw ValidationException::withMessages(['demande' => 'Cette demande a déjà été traitée.']);
        }

        $demande->update(['statut' => 'retiree']);

        return $demande;
    }

    /** Le gérant accepte : l'action demandée est appliquée à la réservation. */
    public function accepter(DemandeModification $demande, User $gerant): DemandeModification
    {
        if (! $demande->estEnAttente()) {
            throw ValidationException::withMessages(['demande' => 'Cette demande a déjà été traitée.']);
        }

        $reservation = $demande->reservation()->firstOrFail();

        if ($demande->type === 'annulation') {
            $this->reservations->annuler(
                $reservation,
                $gerant,
                'Annulation demandée par le client' . ($demande->motif ? " : {$demande->motif}" : ''),
            );
        } else {
            $this->reservations->reporter(
                $reservation,
                $gerant,
                CarbonImmutable::parse($demande->nouvelle_date_debut),
                $demande->nouvelle_duree_heures ? (float) $demande->nouvelle_duree_heures : null,
            );
        }

        $demande->update([
            'statut' => 'acceptee',
            'traite_par' => $gerant->id,
            'traitee_at' => now(),
        ]);
        $demande->journaliser('acceptee');

        $this->notifications->demandeTraitee($demande, true);

        return $demande;
    }

    public function refuser(DemandeModification $demande, User $gerant, ?string $reponse): DemandeModification
    {
        if (! $demande->estEnAttente()) {
            throw ValidationException::withMessages(['demande' => 'Cette demande a déjà été traitée.']);
        }

        $demande->update([
            'statut' => 'refusee',
            'reponse_gerant' => $reponse,
            'traite_par' => $gerant->id,
            'traitee_at' => now(),
        ]);
        $demande->journaliser('refusee');

        $this->notifications->demandeTraitee($demande, false);

        return $demande;
    }
}
