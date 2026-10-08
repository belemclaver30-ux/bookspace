<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
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

    public function connecter()
    {
        $this->validate([
            'telephone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $telephone = User::normaliserTelephone($this->telephone);
        $cle = 'connexion:' . $telephone . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($cle, 5)) {
            throw ValidationException::withMessages([
                'telephone' => 'Trop de tentatives. Réessayez dans ' . RateLimiter::availableIn($cle) . ' secondes.',
            ]);
        }

        // Numéro normalisé, ou tel que saisi (comptes créés avant la normalisation).
        $user = User::whereIn('telephone', [$telephone, trim($this->telephone)])->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($cle, 60);

            throw ValidationException::withMessages(['telephone' => 'Numéro ou mot de passe incorrect.']);
        }

        if (! $user->actif) {
            throw ValidationException::withMessages(['telephone' => 'Compte désactivé.']);
        }

        RateLimiter::clear($cle);
        Auth::login($user, remember: true);
        session()->regenerate();
        $user->forceFill(['derniere_connexion_at' => now()])->save();

        return $this->redirect($user->accueil());
    }

    public function render()
    {
        return view('livewire.auth.connexion');
    }
}
