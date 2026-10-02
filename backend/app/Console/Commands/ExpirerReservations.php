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

        $expirees = Reservation::where('statut', 'en_attente')
            ->where('created_at', '<', $limite)
            ->whereDoesntHave('paiements', fn ($q) => $q->whereIn('statut', ['en_cours', 'valide']))
            ->get()
            ->each(function (Reservation $reservation) use ($notifications) {
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
