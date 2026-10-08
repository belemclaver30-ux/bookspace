<?php

namespace App\Livewire\Auth;

use App\Services\ConnexionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Connexion par numéro de téléphone (TDR §5.1) — session web. */
#[Layout('components.layouts.ecran', ['fond' => 'bg-white'])]
#[Title('Connexion')]
class Connexion extends Component
{
    public string $telephone = '';

    public string $password = '';

    public function connecter(ConnexionService $connexions)
    {
        $this->validate([
            'telephone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = $connexions->verifier($this->telephone, $this->password, request()->ip());

        Auth::login($user, remember: true);
        session()->regenerate();

        return $this->redirect($user->accueil());
    }

    public function render()
    {
        return view('livewire.auth.connexion');
    }
}
