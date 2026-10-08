<?php

namespace App\Livewire\Concerns;

use App\Models\Gerant;

/** Accès au profil gérant de l'utilisateur connecté (pages de l'espace gérant). */
trait AvecGerant
{
    protected function gerant(): Gerant
    {
        return auth()->user()->gerant()->firstOrFail();
    }
}
