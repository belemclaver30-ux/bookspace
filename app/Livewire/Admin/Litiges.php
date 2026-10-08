<?php

namespace App\Livewire\Admin;

use App\Models\Litige;
use App\Services\NotificationService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Litiges client / gérant tranchés par l'administrateur (TDR §5.3). */
#[Layout('components.layouts.dashboard')]
class Litiges extends Component
{
    public const STATUTS = [
        '' => 'Tous',
        'ouvert' => 'Ouverts',
        'en_cours' => 'En cours',
        'resolu' => 'Résolus',
        'ferme' => 'Fermés',
    ];

    public string $statut = '';

    public function filtrer(string $statut): void
    {
        $this->statut = array_key_exists($statut, self::STATUTS) ? $statut : '';
    }

    public function traiter(int $id, string $statut, ?string $resolution = null): void
    {
        abort_unless(in_array($statut, ['en_cours', 'resolu', 'ferme'], true), 422);

        $resolution = mb_substr(trim((string) $resolution), 0, 2000);
        $clos = $statut !== 'en_cours';

        $litige = Litige::whereIn('statut', ['ouvert', 'en_cours'])->findOrFail($id);

        $litige->update([
            'statut' => $statut,
            'resolution' => $resolution !== '' ? $resolution : null,
            'resolu_par' => auth()->id(),
            'resolu_at' => $clos ? now() : null,
        ]);

        app(NotificationService::class)->litigeTraite($litige);
    }

    public function render()
    {
        return view('livewire.admin.litiges', [
            'litiges' => Litige::with(['reservation', 'auteur'])
                ->when($this->statut !== '', fn ($q) => $q->where('statut', $this->statut))
                ->latest()
                ->limit(50)
                ->get(),
            'statuts' => self::STATUTS,
        ]);
    }
}
