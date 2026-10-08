<?php

namespace App\Livewire\Client;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** « Mes réservations » du client (TDR §5.1). */
#[Layout('components.layouts.client')]
#[Title('Mes réservations')]
class MesReservations extends Component
{
    private const PAR_PAGE = 15;

    public const FILTRES = [
        '' => 'Toutes',
        'confirmee' => 'Confirmées',
        'en_attente' => 'En attente',
        'terminee' => 'Terminées',
    ];

    #[Url]
    public string $statut = '';

    /** Nombre de réservations affichées : dans l'URL pour le retour arrière. */
    #[Url(as: 'n', except: self::PAR_PAGE)]
    public int $limite = self::PAR_PAGE;

    public function filtrer(string $statut): void
    {
        $this->statut = array_key_exists($statut, self::FILTRES) ? $statut : '';
        $this->limite = self::PAR_PAGE;
    }

    public function voirPlus(): void
    {
        $this->limite += self::PAR_PAGE;
    }

    public function render()
    {
        $requete = auth()->user()->reservations()
            ->when(array_key_exists($this->statut, self::FILTRES) && $this->statut !== '', fn ($q) => $q->where('statut', $this->statut));

        $total = (clone $requete)->count();

        return view('livewire.client.mes-reservations', [
            'reservations' => $requete->with(['espace', 'evaluation'])->latest('date_debut')->limit($this->limite)->get(),
            'reste' => $total > $this->limite,
            'filtres' => self::FILTRES,
        ]);
    }
}
