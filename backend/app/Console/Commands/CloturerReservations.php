<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

/**
 * Passe en `terminee` les réservations confirmées dont le créneau est écoulé.
 * Débloque de fait la possibilité d'évaluer l'espace (TDR §5.1).
 * Planifiée toutes les 15 min (voir routes/console.php).
 */
class CloturerReservations extends Command
{
    protected $signature = 'reservations:cloturer';

    protected $description = 'Marque comme terminées les réservations confirmées dont le créneau est passé';

    public function handle(): int
    {
        $cloturees = Reservation::where('statut', 'confirmee')
            ->where('date_fin', '<', now())
            ->get()
            ->each(function (Reservation $reservation) {
                $reservation->update(['statut' => 'terminee']);
                $reservation->journaliser('terminee');
            });

        $this->info("{$cloturees->count()} réservation(s) clôturée(s).");

        return self::SUCCESS;
    }
}
