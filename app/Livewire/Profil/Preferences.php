<?php

namespace App\Livewire\Profil;

use App\Livewire\Concerns\DansLeProfil;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Profil > Préférences : notifications par SMS et rappels avant le créneau.
 * Chaque réglage est enregistré dès qu'il est modifié. Le fil de notifications
 * de l'application, lui, est toujours alimenté.
 */
#[Title('Préférences')]
class Preferences extends Component
{
    use DansLeProfil;

    public bool $notifSms = true;

    public bool $notifRappels = true;

    /** Réglage qui vient d'être enregistré (retour visuel). */
    public ?string $enregistre = null;

    public function mount(): void
    {
        $user = auth()->user();

        $this->notifSms = $user->accepteSms();
        $this->notifRappels = $user->accepteRappels();
    }

    public function updatedNotifSms(bool $actif): void
    {
        auth()->user()->update(['notif_sms' => $actif]);
        $this->enregistre = 'sms';
    }

    public function updatedNotifRappels(bool $actif): void
    {
        auth()->user()->update(['notif_rappels' => $actif]);
        $this->enregistre = 'rappels';
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.profil.preferences', [
            'user' => $user,
            'nonLues' => $user->notifications()->whereNull('lu_at')->count(),
            'minutesRappel' => (int) config('bookspace.rappel_minutes_avant', 30),
        ])->layout($this->coque());
    }
}
