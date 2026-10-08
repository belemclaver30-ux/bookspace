<?php

namespace App\Services;

use App\Models\Espace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Vérification de disponibilité d'un créneau (TDR §8 : « Vérifier la
 * disponibilité »). Un créneau est disponible s'il est :
 *   - à l'intérieur des horaires d'ouverture du jour ;
 *   - hors de toute plage bloquée par le gérant ;
 *   - sans chevauchement avec une réservation en_attente ou confirmee.
 */
class DisponibiliteService
{
    public function estDisponible(Espace $espace, CarbonImmutable $debut, CarbonImmutable $fin, ?int $ignorerReservationId = null): bool
    {
        if ($fin->lessThanOrEqualTo($debut)) {
            return false;
        }

        return $this->dansHorairesOuverture($espace, $debut, $fin)
            && ! $this->estBloque($espace, $debut, $fin)
            && ! $this->chevaucheReservation($espace, $debut, $fin, $ignorerReservationId);
    }

    public function dansHorairesOuverture(Espace $espace, CarbonImmutable $debut, CarbonImmutable $fin): bool
    {
        // Un créneau à cheval sur deux jours n'est pas géré (réservation à la journée).
        if (! $debut->isSameDay($fin)) {
            return false;
        }

        return $espace->disponibilites()
            ->where('jour_semaine', $debut->dayOfWeek)
            ->whereTime('heure_ouverture', '<=', $debut->format('H:i:s'))
            ->whereTime('heure_fermeture', '>=', $fin->format('H:i:s'))
            ->exists();
    }

    public function estBloque(Espace $espace, CarbonImmutable $debut, CarbonImmutable $fin): bool
    {
        return $espace->blocages()
            ->where('date_debut', '<', $fin)
            ->where('date_fin', '>', $debut)
            ->exists();
    }

    public function chevaucheReservation(Espace $espace, CarbonImmutable $debut, CarbonImmutable $fin, ?int $ignorerReservationId = null): bool
    {
        return $espace->reservations()
            ->whereIn('statut', ['en_attente', 'confirmee'])
            ->when($ignorerReservationId, fn ($q) => $q->whereKeyNot($ignorerReservationId))
            ->where('date_debut', '<', $fin)
            ->where('date_fin', '>', $debut)
            ->exists();
    }

    /**
     * Créneaux de début possibles (pas de 1 h) d'une journée, avec leur état
     * libre / réservé / bloqué — alimente l'écran « Choisir un créneau ».
     * `disponible` tient compte de la durée souhaitée : un créneau qui ne
     * « rentre pas » (fin après fermeture, chevauchement…) est marqué occupé,
     * de même qu'un créneau déjà passé.
     *
     * @return Collection<int, array{heure:string, disponible:bool}>
     */
    public function creneauxDuJour(Espace $espace, CarbonImmutable $jour, float $dureeHeures = 1.0): Collection
    {
        $ouverture = $espace->disponibilites()
            ->where('jour_semaine', $jour->dayOfWeek)
            ->orderBy('heure_ouverture')
            ->get();

        $maintenant = CarbonImmutable::now();
        $dureeMinutes = (int) round(max($dureeHeures, 1) * 60);

        return $ouverture->flatMap(function ($plage) use ($espace, $jour, $maintenant, $dureeMinutes) {
            $curseur = $jour->setTimeFromTimeString($plage->heure_ouverture);
            $ferme = $jour->setTimeFromTimeString($plage->heure_fermeture);
            $slots = collect();

            while ($curseur->lessThan($ferme)) {
                $slotFin = $curseur->addMinutes($dureeMinutes);
                $slots->push([
                    'heure' => $curseur->format('H:i'),
                    'disponible' => $curseur->greaterThan($maintenant)
                        && $this->estDisponible($espace, $curseur, $slotFin),
                ]);
                $curseur = $curseur->addHour();
            }

            return $slots;
        })->values();
    }
}
