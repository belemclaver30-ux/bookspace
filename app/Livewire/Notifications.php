<?php

namespace App\Livewire;

use App\Livewire\Concerns\DansLeProfil;
use App\Models\AppNotification;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Notifications in-app de l'utilisateur connecté (TDR §5.1). Chaque
 * notification se déplie au clic : texte complet, lien vers ce qu'elle
 * concerne, et elle est marquée comme lue.
 */
#[Title('Notifications')]
class Notifications extends Component
{
    use DansLeProfil;

    /** Notification actuellement dépliée. */
    public ?int $ouverte = null;

    public function basculer(int $id): void
    {
        $this->ouverte = $this->ouverte === $id ? null : $id;

        if ($this->ouverte !== null) {
            auth()->user()->notifications()->whereKey($id)->whereNull('lu_at')->update(['lu_at' => now()]);
            $this->signalerCompteur();
        }
    }

    public function toutMarquerLu(): void
    {
        auth()->user()->notifications()->whereNull('lu_at')->update(['lu_at' => now()]);
        $this->signalerCompteur();
    }

    /** Met à jour les pastilles de la navigation sans recharger la page. */
    private function signalerCompteur(): void
    {
        $this->dispatch('compteur', type: 'notifications', nombre: auth()->user()->notifications()->whereNull('lu_at')->count());
    }

    public function render()
    {
        $user = auth()->user();
        $notifications = $user->notifications()->latest()->limit(60)->get();

        $groupes = $notifications->groupBy(function (AppNotification $n) {
            return match (true) {
                $n->created_at->isToday() => "Aujourd'hui",
                $n->created_at->isYesterday() => 'Hier',
                $n->created_at->greaterThan(now()->subDays(7)) => 'Cette semaine',
                default => 'Plus ancien',
            };
        });

        return view('livewire.notifications', [
            'user' => $user,
            'groupes' => $groupes,
            'nonLues' => $notifications->whereNull('lu_at')->count(),
            'liens' => $notifications->mapWithKeys(fn (AppNotification $n) => [$n->id => $this->lien($n, $user)]),
        ])->layout($this->coque());
    }

    /** Page liée à la notification : [libellé, url] ou null. */
    private function lien(AppNotification $n, User $user): ?array
    {
        $reservationId = $n->data['reservation_id'] ?? null;

        return match (true) {
            $n->type === 'litige_traite' => ['Voir mon signalement', '/profil/aide#signalement'],
            $n->type === 'demande_modification' => ['Traiter la demande', '/gerant/demandes'],
            $reservationId && $user->estClient() => ['Voir la réservation', "/reservations/{$reservationId}"],
            $reservationId && $user->estGerant() => ['Voir mes réservations', '/gerant/reservations'],
            default => null,
        };
    }
}
