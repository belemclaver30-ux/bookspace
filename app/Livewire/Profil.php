<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Profil de l'utilisateur connecté — affiché dans la coque de son rôle. */
#[Title('Profil')]
class Profil extends Component
{
    public function render()
    {
        $user = auth()->user()->loadMissing('gerant');

        return view('livewire.profil', ['user' => $user, 'activite' => $this->activite($user)])
            ->layout($user->estClient() ? 'components.layouts.client' : 'components.layouts.dashboard');
    }

    /** Quelques chiffres sous le nom : [valeur, libellé] × 3, selon le rôle. */
    private function activite(User $user): array
    {
        $pluriel = fn (int $n, string $mot) => [$n, $mot . ($n > 1 ? 's' : '')];

        if ($user->estClient()) {
            return [
                $pluriel($user->reservations()->whereIn('statut', ['confirmee', 'terminee'])->count(), 'réservation'),
                $pluriel($user->evaluations()->count(), 'avis'),
                $pluriel($user->favoris()->count(), 'favori'),
            ];
        }

        if ($gerant = $user->gerant) {
            $note = (float) $gerant->espaces()->where('nb_avis', '>', 0)->avg('note_moyenne');

            return [
                $pluriel($gerant->espaces()->where('statut', 'valide')->count(), 'espace'),
                $pluriel($gerant->reservations()->whereIn('statut', ['confirmee', 'terminee'])->count(), 'réservation'),
                [$note > 0 ? number_format($note, 1, ',', '') . ' ★' : '—', 'note moyenne'],
            ];
        }

        return [];
    }
}
