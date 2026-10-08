<?php

namespace App\Livewire\Profil;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Profil > Mes favoris : espaces mis de côté par le client pour réserver à nouveau. */
#[Layout('components.layouts.client')]
#[Title('Mes favoris')]
class Favoris extends Component
{
    public function retirer(int $espaceId): void
    {
        auth()->user()->favoris()->detach($espaceId);
    }

    public function render()
    {
        return view('livewire.profil.favoris', [
            // Un espace suspendu ou retiré n'apparaît plus, sans perdre le favori.
            'espaces' => auth()->user()->favoris()->publies()->with('photos')
                ->orderByPivot('created_at', 'desc')->get(),
        ]);
    }
}
