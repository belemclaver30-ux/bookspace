<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * Annule les réservations « en_attente » dont l'acompte n'a pas été réglé dans
 * le délai imparti (`bookspace.reservation_expiration_minutes`). Sans cela, une
 * réservation jamais payée bloquerait le créneau indéfiniment
 * (`DisponibiliteService::chevaucheReservation` compte les `en_attente`).
 * Planifiée toutes les 5 min (voir routes/console.php).
 */
class ExpirerReservations extends Command
{
    protected $signature = 'reservations:expirer';

    protected $description = "Annule les réservations en attente dont l'acompte n'a pas été réglé à temps";

    public function handle(NotificationService $notifications): int
    {
        $delai = (int) config('bookspace.reservation_expiration_minutes', 30);
        $limite = now()->subMinutes($delai);
        // Un paiement lancé obtient un sursis, mais pas indéfini : sinon il
        // suffirait d'ouvrir la page de paiement sans payer pour bloquer un créneau.
        $limitePaiement = now()->subMinutes((int) config('bookspace.paiement_expiration_minutes', 60));

        $expirees = Reservation::where('statut', 'en_attente')
            ->where('created_at', '<', $limite)
            ->whereDoesntHave('paiements', fn ($q) => $q->where('statut', 'valide'))
            ->whereDoesntHave('paiements', fn ($q) => $q->where('statut', 'en_cours')->where('created_at', '>=', $limitePaiement))
            ->get()
            ->each(function (Reservation $reservation) use ($notifications) {
                // Un éventuel webhook tardif sera traité par PaiementService::confirmerCallback.
                $reservation->paiements()->where('statut', 'en_cours')->update(['statut' => 'echoue']);
                $reservation->update([
                    'statut' => 'annulee',
                    'motif_annulation' => "Acompte non réglé dans le délai imparti — réservation expirée.",
                    'annulee_at' => now(),
                ]);
                $reservation->journaliser('expiree');
                $notifications->reservationExpiree($reservation);
            });

        $this->info("{$expirees->count()} réservation(s) expirée(s).");

        return self::SUCCESS;
    }
}
