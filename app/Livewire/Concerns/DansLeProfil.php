<?php

namespace App\Livewire\Concerns;

/**
 * Sous-pages du profil : affichées dans la coque du rôle (barre de navigation
 * client, ou back-office gérant / administrateur), comme le profil lui-même.
 */
trait DansLeProfil
{
    protected function coque(): string
    {
        return auth()->user()->estClient() ? 'components.layouts.client' : 'components.layouts.dashboard';
    }
}
