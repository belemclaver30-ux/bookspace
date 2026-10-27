<?php

namespace App\Services;

use App\Models\Abonnement;
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

        // Le fil in-app est toujours alimenté ; le SMS respecte la préférence
        // de l'utilisateur (Profil > Préférences).
        if ($canal === 'sms' && $user->telephone && $user->accepteSms()) {
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

        // Chacun peut désactiver les rappels dans ses préférences.
        if (! $reservation->client || $reservation->client->accepteRappels()) {
            $this->notifierClient(
                $reservation,
                'reservation_rappel',
                'Rappel de réservation',
                "Votre réservation pour « {$reservation->espace->nom} » commence à {$reservation->date_debut->format('H:i')}.",
            );
        }

        $proprietaire = $reservation->espace->gerant->user ?? null;

        if ($proprietaire?->accepteRappels()) {
            $nomClient = $reservation->client?->name ?? $reservation->client_nom ?? 'Votre client';

            $this->notifier(
                $proprietaire,
                'reservation_rappel',
                'Rappel de réservation',
                "{$nomClient} arrive à {$reservation->date_debut->format('H:i')} pour « {$reservation->espace->nom} ».",
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

    public function abonnementCree(Abonnement $abonnement): void
    {
        $this->notifier(
            $abonnement->client,
            'abonnement_cree',
            'Abonnement enregistré',
            "Votre abonnement {$abonnement->reference} ({$abonnement->nombre_seances} séances) est en attente de paiement de l'acompte.",
            ['abonnement_id' => $abonnement->id],
        );
    }

    /** Acompte unique validé : toutes les séances sont confirmées d'un coup, une seule notification. */
    public function abonnementConfirme(Abonnement $abonnement): void
    {
        $abonnement->loadMissing('espace.gerant.user', 'client');
        $premiere = $abonnement->reservations()->oldest('date_debut')->first();
        $depart = $premiere ? $premiere->date_debut->format('d/m/Y à H:i') : '—';

        $this->notifier(
            $abonnement->client,
            'abonnement_confirme',
            'Abonnement confirmé',
            "Votre abonnement « {$abonnement->espace->nom} » est confirmé : {$abonnement->nombre_seances} séances, à partir du {$depart}.",
            ['abonnement_id' => $abonnement->id],
            'sms',
        );

        if ($proprietaire = $abonnement->espace->gerant->user ?? null) {
            $this->notifier(
                $proprietaire,
                'nouvel_abonnement',
                'Nouvel abonnement reçu',
                "{$abonnement->client->name} s'est abonné à « {$abonnement->espace->nom} » : {$abonnement->nombre_seances} séances, à partir du {$depart}.",
                ['abonnement_id' => $abonnement->id],
                'sms',
            );
        }
    }

    public function abonnementAnnulee(Abonnement $abonnement): void
    {
        $abonnement->loadMissing('espace.gerant.user', 'client');

        foreach ([$abonnement->client, $abonnement->espace->gerant->user ?? null] as $destinataire) {
            if ($destinataire) {
                $this->notifier(
                    $destinataire,
                    'abonnement_annulee',
                    'Abonnement annulé',
                    "L'abonnement {$abonnement->reference} a été annulé.",
                    ['abonnement_id' => $abonnement->id],
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

        $this->notifierClient(
            $reservation,
            'reservation_reportee',
            'Réservation déplacée',
            "Votre réservation « {$reservation->espace->nom} » du {$ancienDebut->format('d/m/Y à H:i')} "
            . "est reportée au {$reservation->date_debut->format('d/m/Y à H:i')}.",
        );
    }

    /**
     * Alerte de sécurité après un changement de mot de passe (oubli ou profil).
     * Comme les codes de vérification, le SMS part même si l'utilisateur a
     * coupé les SMS : c'est le seul moyen de détecter un piratage du compte.
     */
    public function motDePasseModifie(User $user): void
    {
        $support = config('bookspace.support.whatsapp') ?: config('bookspace.support.telephone') ?: config('bookspace.support.email');
        $contenu = 'Le mot de passe de votre compte a été modifié le ' . now()->format('d/m/Y à H:i') . '. '
            . "Si ce n'est pas vous, contactez immédiatement le support" . ($support ? " ({$support})" : '') . '.';

        $this->notifier($user, 'mot_de_passe_modifie', 'Mot de passe modifié', $contenu);

        if ($user->telephone) {
            $this->sms->envoyer($user->telephone, "BookSpace : {$contenu}");
        }
    }

    /** BookSpace a versé au gérant sa part des acomptes (SMS + fil in-app). */
    public function reversementEffectue(\App\Models\Reversement $reversement): void
    {
        $reversement->loadMissing('gerant.user');
        $operateur = \App\Models\Gerant::OPERATEURS[$reversement->momo_operateur] ?? $reversement->momo_operateur;

        $this->notifier(
            $reversement->gerant->user,
            'reversement_effectue',
            'Versement effectué',
            'BookSpace vous a versé ' . fcfa($reversement->montant) . " sur {$operateur} ({$reversement->momo_numero}) "
            . "pour {$reversement->nb_reservations} réservation(s). Référence : {$reversement->reference_operateur}.",
            ['reversement_id' => $reversement->id],
            'sms',
        );
    }

    /** L'administrateur a pris en charge ou clos un signalement : son auteur est prévenu. */
    public function litigeTraite(\App\Models\Litige $litige): void
    {
        $litige->loadMissing('auteur', 'reservation');

        if (! $litige->auteur) {
            return;
        }

        $etat = ['en_cours' => 'pris en charge', 'resolu' => 'résolu', 'ferme' => 'clos'][$litige->statut] ?? $litige->statut;

        $this->notifier(
            $litige->auteur,
            'litige_traite',
            "Signalement {$etat}",
            "Votre signalement « {$litige->sujet} » a été {$etat} par l'équipe BookSpace."
            . ($litige->resolution ? " Réponse : {$litige->resolution}" : ''),
            ['litige_id' => $litige->id, 'reservation_id' => $litige->reservation_id],
        );
    }

    /**
     * SMS + fil in-app au client d'une réservation. Une réservation saisie à
     * la main par le gérant n'a pas de compte client : seul le SMS part, au
     * numéro noté sur la réservation.
     */
    private function notifierClient(Reservation $reservation, string $type, string $titre, string $contenu): void
    {
        if ($reservation->client) {
            $this->notifier($reservation->client, $type, $titre, $contenu, ['reservation_id' => $reservation->id], 'sms');
        } elseif ($reservation->client_telephone) {
            $this->sms->envoyer($reservation->client_telephone, "{$titre} — {$contenu}");
        }
    }
}
