<?php

namespace App\Livewire\Gerant;

use App\Livewire\Concerns\AvecGerant;
use App\Services\StatistiquesService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Tableau de bord du gérant : revenus, occupation, répartition (TDR §5.2). */
#[Layout('components.layouts.dashboard')]
class TableauDeBord extends Component
{
    use AvecGerant;

    public function render()
    {
        $gerant = $this->gerant();

        return view('livewire.gerant.tableau-de-bord', [
            'gerant' => $gerant,
            'stats' => app(StatistiquesService::class)->gerant($gerant),
        ]);
    }
}
