<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\CodeVerificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Mot de passe oublié : un code à 6 chiffres est envoyé par SMS au numéro du
 * compte, puis l'utilisateur choisit un nouveau mot de passe. La réponse ne
 * révèle jamais si un numéro possède un compte.
 */
#[Layout('components.layouts.ecran', ['fond' => 'bg-white'])]
#[Title('Mot de passe oublié')]
class MotDePasseOublie extends Component
{
    /** 'telephone' (saisie du numéro) puis 'code' (code + nouveau mot de passe). */
    public string $etape = 'telephone';

    public string $telephone = '';

    public string $code = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function envoyerCode(CodeVerificationService $codes): void
    {
        $this->validate(['telephone' => ['required', 'string', 'max:30']], [], ['telephone' => 'numéro de téléphone']);
        $this->telephone = User::normaliserTelephone($this->telephone);

        $cle = 'mot-de-passe-oublie:' . request()->ip();
        if (RateLimiter::tooManyAttempts($cle, 10)) {
            throw ValidationException::withMessages(['telephone' => 'Trop de demandes. Réessayez dans une heure.']);
        }
        RateLimiter::hit($cle, 3600);

        if ($user = $this->compte()) {
            $codes->envoyer($user, 'reinitialisation', $user->telephone);
        }

        $this->resetErrorBag();
        $this->etape = 'code';
    }

    public function reinitialiser(CodeVerificationService $codes)
    {
        $this->validate([
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'code.digits' => 'Le code comporte 6 chiffres.',
        ], [
            'password' => 'nouveau mot de passe',
        ]);

        $user = $this->compte();

        if (! $user) {
            throw ValidationException::withMessages(['code' => 'Ce code a expiré. Demandez-en un nouveau.']);
        }

        $codes->verifier($user, 'reinitialisation', $this->code);

        $user->forceFill(['password' => $this->password, 'remember_token' => null])->save();
        $user->tokens()->delete(); // jetons d'API éventuels

        Auth::login($user, remember: true);
        session()->regenerate();

        return $this->redirect($user->accueil());
    }

    public function recommencer(): void
    {
        $this->reset('code', 'password', 'password_confirmation');
        $this->resetErrorBag();
        $this->etape = 'telephone';
    }

    public function render()
    {
        return view('livewire.auth.mot-de-passe-oublie', [
            'validite' => CodeVerificationService::VALIDITE_MINUTES,
        ]);
    }

    /** Compte actif correspondant au numéro saisi, s'il existe. */
    private function compte(): ?User
    {
        return User::where('telephone', $this->telephone)->where('actif', true)->first();
    }
}
