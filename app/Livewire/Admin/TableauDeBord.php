<?php

namespace App\Livewire\Admin;

use App\Services\StatistiquesService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Supervision de la plateforme (TDR §5.3). */
#[Layout('components.layouts.dashboard')]
class TableauDeBord extends Component
{
    public function render()
    {
        return view('livewire.admin.tableau-de-bord', [
            'tb' => app(StatistiquesService::class)->plateforme(),
        ]);
    }
}
