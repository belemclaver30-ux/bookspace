<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\InscriptionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Création d'un compte client ou gérant (TDR §5.1). */
#[Layout('components.layouts.ecran', ['fond' => 'bg-white'])]
#[Title('Créer un compte')]
class Inscription extends Component
{
    #[Url]
    public string $role = 'client';

    // Conservés en session : l'utilisateur peut aller lire les conditions
    // d'utilisation puis revenir sans perdre sa saisie (mot de passe exclu).
    #[Session]
    public string $name = '';

    #[Session]
    public string $telephone = '';

    #[Session]
    public string $email = '';

    public string $password = '';

    #[Session]
    public string $raison_sociale = '';

    #[Session]
    public bool $accepte = false;

    public function mount(): void
    {
        if (! in_array($this->role, ['client', 'gerant'], true)) {
            $this->role = 'client';
        }
    }

    public function inscrire(InscriptionService $inscriptions)
    {
        $this->telephone = User::normaliserTelephone($this->telephone);
        $this->name = trim($this->name);
        $this->email = trim($this->email);
        $this->raison_sociale = trim($this->raison_sociale);

        $data = $this->validate(
            InscriptionService::regles() + ['accepte' => ['accepted']],
            ['accepte.accepted' => "Vous devez accepter les conditions d'utilisation."],
        );

        $user = $inscriptions->inscrire([
            ...$data,
            'email' => $data['email'] ?: null,
            'raison_sociale' => $data['role'] === 'gerant' ? $data['raison_sociale'] : null,
        ]);

        $this->reset('name', 'telephone', 'email', 'raison_sociale', 'accepte');

        Auth::login($user, remember: true);
        session()->regenerate();

        return $this->redirect($user->accueil());
    }

    public function render()
    {
        return view('livewire.auth.inscription');
    }
}
