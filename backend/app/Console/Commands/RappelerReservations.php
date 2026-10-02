<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * Envoie un rappel SMS au client et au gérant ~30 min avant le début du
 * créneau. Fenêtre plus large que la fréquence de planification (voir
 * routes/console.php) pour ne rater aucune réservation ; `rappel_envoye_at`
 * évite les doublons d'un passage à l'autre.
 */
class RappelerReservations extends Command
{
    protected $signature = 'reservations:rappeler';

    protected $description = 'Envoie un rappel au client et au gérant peu avant le créneau réservé';

    public function handle(NotificationService $notifications): int
    {
        $delai = (int) config('bookspace.rappel_minutes_avant', 30);
        $fenetreDebut = now()->addMinutes($delai);
        $fenetreFin = now()->addMinutes($delai + 10);

        $aRappeler = Reservation::where('statut', 'confirmee')
            ->whereNull('rappel_envoye_at')
            ->whereBetween('date_debut', [$fenetreDebut, $fenetreFin])
            ->get()
            ->each(function (Reservation $reservation) use ($notifications) {
                $reservation->update(['rappel_envoye_at' => now()]);
                $notifications->reservationRappel($reservation);
            });

        $this->info("{$aRappeler->count()} rappel(s) envoyé(s).");

        return self::SUCCESS;
    }
}
