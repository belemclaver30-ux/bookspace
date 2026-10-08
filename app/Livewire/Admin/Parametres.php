<?php

namespace App\Livewire\Admin;

use App\Models\Parametre;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Paramètres de la plateforme : commission, acompte, délai d'annulation (TDR §5.3). */
#[Layout('components.layouts.dashboard')]
class Parametres extends Component
{
    /** Règles propres aux paramètres connus ; les autres sont de simples textes. */
    private const REGLES = [
        'commission_rate' => ['required', 'numeric', 'between:0,100'],
        'deposit_rate' => ['required', 'numeric', 'between:0,100'],
        'annulation_delai_heures' => ['required', 'integer', 'between:0,720'],
    ];

    /** @var array<string, string> valeur saisie par clé de paramètre */
    public array $valeurs = [];

    public ?string $enregistre = null;

    public function mount(): void
    {
        $this->valeurs = Parametre::pluck('valeur', 'cle')->map(fn ($v) => (string) $v)->all();
    }

    public function enregistrer(string $cle): void
    {
        $this->enregistre = null;
        abort_unless(Parametre::query()->whereKey($cle)->exists(), 404);

        $this->validate(
            ["valeurs.{$cle}" => self::REGLES[$cle] ?? ['required', 'string', 'max:255']],
            [],
            ["valeurs.{$cle}" => 'valeur'],
        );

        Parametre::definir($cle, trim($this->valeurs[$cle]));
        $this->enregistre = $cle;
    }

    public function render()
    {
        return view('livewire.admin.parametres', ['parametres' => Parametre::orderBy('cle')->get()]);
    }
}
