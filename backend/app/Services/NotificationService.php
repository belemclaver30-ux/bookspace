<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Sms\PasserelleSms;

/**
 * Communication automatique client <-> gérant (TDR §2.2, §8).
 * Chaque notification est enregistrée en base (fil in-app) ; si le canal est
 * `sms`, elle est aussi remise à la passerelle SMS.
 */
class NotificationService
{
    public function __construct(private readonly PasserelleSms $sms) {}

    public function notifier(User $user, string $type, string $titre, string $contenu, array $data = [], string $canal = 'push'): AppNotification
    {
        $notification = AppNotification::create([
            'user_id' => $user->id,
            'canal' => $canal,
            'type' => $type,
            'titre' => $titre,
            'contenu' => $contenu,
            'data' => $data,
            'envoye_at' => now(),
        ]);

        if ($canal === 'sms' && $user->telephone) {
            $this->sms->envoyer($user->telephone, "{$titre} — {$contenu}");
        }

        return $notification;
    }

    public function reservationCreee(Reservation $reservation): void
    {
        $this->notifier(
            $reservation->client,
            'reservation_creee',
            'Réservation enregistrée',
            "Votre réservation {$reservation->reference} est en attente de paiement de l'acompte.",
            ['reservation_id' => $reservation->id],
        );
    }

    public function reservationConfirmee(Reservation $reservation): void
    {
        $reservation->loadMissing('espace.gerant.user');

        $this->notifier(
            $reservation->client,
            'reservation_confirmee',
            'Réservation confirmée',
            "Votre créneau du {$reservation->date_debut->format('d/m/Y à H:i')} pour « {$reservation->espace->nom} » est confirmé.",
            ['reservation_id' => $reservation->id],
            'sms',
        );

        if ($proprietaire = $reservation->espace->gerant->user ?? null) {
            // SMS (pas seulement push) : le gérant doit être averti même s'il
            // n'a pas l'app ouverte au moment de la réservation.
            $this->notifier(
                $proprietaire,
                'nouvelle_reservation',
                'Nouvelle réservation reçue',
                "{$reservation->client->name} a réservé « {$reservation->espace->nom} » le {$reservation->date_debut->format('d/m/Y à H:i')}.",
                ['reservation_id' => $reservation->id],
                'sms',
            );
        }
    }

    /** Rappel envoyé au client et au gérant ~30 min avant le créneau. */
    public function reservationRappel(Reservation $reservation): void
    {
        $reservation->loadMissing('espace.gerant.user', 'client');

        $this->notifier(
            $reservation->client,
            'reservation_rappel',
            'Rappel de réservation',
            "Votre réservation pour « {$reservation->espace->nom} » commence à {$reservation->date_debut->format('H:i')}.",
            ['reservation_id' => $reservation->id],
            'sms',
        );

        if ($proprietaire = $reservation->espace->gerant->user ?? null) {
            $this->notifier(
                $proprietaire,
                'reservation_rappel',
                'Rappel de réservation',
                "{$reservation->client->name} arrive à {$reservation->date_debut->format('H:i')} pour « {$reservation->espace->nom} ».",
                ['reservation_id' => $reservation->id],
                'sms',
            );
        }
    }

    public function reservationExpiree(Reservation $reservation): void
    {
        $reservation->loadMissing('espace', 'client');

        $this->notifier(
            $reservation->client,
            'reservation_expiree',
            'Réservation expirée',
            "Votre réservation {$reservation->reference} pour « {$reservation->espace->nom} » a été annulée : "
            . "l'acompte n'a pas été réglé à temps. Le créneau est de nouveau disponible.",
            ['reservation_id' => $reservation->id],
        );
    }

    public function reservationAnnulee(Reservation $reservation): void
    {
        $reservation->loadMissing('espace.gerant.user', 'client');

        foreach ([$reservation->client, $reservation->espace->gerant->user ?? null] as $destinataire) {
            if ($destinataire) {
                $this->notifier(
                    $destinataire,
                    'reservation_annulee',
                    'Réservation annulée',
                    "La réservation {$reservation->reference} a été annulée.",
                    ['reservation_id' => $reservation->id],
                );
            }
        }
    }

    public function demandeRecue(\App\Models\DemandeModification $demande): void
    {
        $demande->loadMissing('reservation.espace.gerant.user', 'reservation.client');
        $gerant = $demande->reservation->espace->gerant->user ?? null;
        if (! $gerant) {
            return;
        }

        $quoi = $demande->type === 'annulation' ? "l'annulation" : 'un report';
        $this->notifier(
            $gerant,
            'demande_modification',
            'Nouvelle demande client',
            "{$demande->reservation->client->name} demande {$quoi} pour la réservation {$demande->reservation->reference}.",
            ['reservation_id' => $demande->reservation_id, 'demande_id' => $demande->id],
        );
    }

    public function demandeTraitee(\App\Models\DemandeModification $demande, bool $acceptee): void
    {
        $demande->loadMissing('reservation.client', 'reservation.espace');
        $quoi = $demande->type === 'annulation' ? "d'annulation" : 'de report';
        $verbe = $acceptee ? 'acceptée' : 'refusée';

        $this->notifier(
            $demande->reservation->client,
            'demande_' . ($acceptee ? 'acceptee' : 'refusee'),
            "Demande {$verbe}",
            "Votre demande {$quoi} pour « {$demande->reservation->espace->nom} » a été {$verbe} par le gérant."
            . ($demande->reponse_gerant ? " Message : {$demande->reponse_gerant}" : ''),
            ['reservation_id' => $demande->reservation_id],
            'sms',
        );
    }

    public function reservationReportee(Reservation $reservation, \Carbon\CarbonInterface $ancienDebut): void
    {
        $reservation->loadMissing('espace', 'client');

        $this->notifier(
            $reservation->client,
            'reservation_reportee',
            'Réservation déplacée',
            "Votre réservation « {$reservation->espace->nom} » du {$ancienDebut->format('d/m/Y à H:i')} "
            . "est reportée au {$reservation->date_debut->format('d/m/Y à H:i')}.",
            ['reservation_id' => $reservation->id],
            'sms',
        );
    }
}
