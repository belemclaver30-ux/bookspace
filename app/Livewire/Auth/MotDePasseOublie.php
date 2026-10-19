<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\CodeVerificationService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Mot de passe oublié, en trois étapes :
 * 1. `telephone` : un code à 6 chiffres est envoyé par SMS au numéro du compte ;
 * 2. `code` : le code est vérifié (renvoi possible après un délai) ;
 * 3. `nouveau` : l'utilisateur choisit son nouveau mot de passe.
 * Plus `support` : l'utilisateur n'a plus accès à son numéro.
 *
 * La réponse ne révèle jamais si un numéro possède un compte. La validation
 * du code est mémorisée côté serveur (session) : modifier `etape` depuis le
 * navigateur ne permet pas de sauter l'étape du code.
 */
#[Layout('components.layouts.ecran', ['fond' => 'bg-white'])]
#[Title('Mot de passe oublié')]
class MotDePasseOublie extends Component
{
    /** Délai avant de pouvoir redemander un code (secondes). */
    public const DELAI_RENVOI = 60;

    /** Temps laissé pour choisir le mot de passe une fois le code validé (minutes). */
    private const VALIDITE_VERIFICATION = 10;

    private const CLE_SESSION = 'mot_de_passe_oublie';

    /** 'telephone' | 'code' | 'nouveau' | 'support' */
    public string $etape = 'telephone';

    public string $telephone = '';

    public string $code = '';

    public string $password = '';

    /** Horodatage à partir duquel un nouveau code peut être demandé. */
    #[Locked]
    public int $renvoiPossibleA = 0;

    public function envoyerCode(CodeVerificationService $codes): void
    {
        $this->validate(['telephone' => ['required', 'string', 'max:30']], [], ['telephone' => 'numéro de téléphone']);
        $this->telephone = User::normaliserTelephone($this->telephone);

        $this->expedier($codes);

        $this->reset('code');
        $this->resetErrorBag();
        $this->etape = 'code';
    }

    public function renvoyerCode(CodeVerificationService $codes): void
    {
        if ($this->etape !== 'code' || $this->telephone === '') {
            return;
        }

        if (now()->timestamp < $this->renvoiPossibleA) {
            throw ValidationException::withMessages(['code' => 'Patientez quelques secondes avant de redemander un code.']);
        }

        $this->expedier($codes);

        $this->reset('code');
        $this->resetErrorBag();
        session()->flash('code_renvoye', true);
    }

    public function verifierCode(CodeVerificationService $codes): void
    {
        $this->code = preg_replace('/\D/', '', $this->code);
        $this->validate(['code' => ['required', 'digits:6']], ['code.digits' => 'Le code comporte 6 chiffres.']);

        $user = $this->compte();

        if (! $user) {
            // Même réponse qu'un code faux : on ne révèle pas l'absence de compte.
            throw ValidationException::withMessages(['code' => 'Code incorrect ou expiré. Demandez-en un nouveau.']);
        }

        $codes->verifier($user, 'reinitialisation', $this->code);

        session()->put(self::CLE_SESSION, [
            'user_id' => $user->id,
            'expire' => now()->addMinutes(self::VALIDITE_VERIFICATION)->timestamp,
        ]);

        $this->resetErrorBag();
        $this->etape = 'nouveau';
    }

    public function reinitialiser(NotificationService $notifications)
    {
        $this->validate(
            ['password' => ['required', 'string', 'min:8', 'max:255']],
            ['password.min' => 'Choisissez au moins 8 caractères.'],
            ['password' => 'nouveau mot de passe'],
        );

        $verification = session(self::CLE_SESSION);
        $user = ($verification['expire'] ?? 0) >= now()->timestamp
            ? User::where('actif', true)->find($verification['user_id'] ?? null)
            : null;

        if (! $user) {
            session()->forget(self::CLE_SESSION);
            $this->reset('code', 'password');
            $this->etape = $this->telephone !== '' ? 'code' : 'telephone';

            throw ValidationException::withMessages([
                $this->etape === 'code' ? 'code' : 'telephone' => 'Le délai est dépassé. Demandez un nouveau code.',
            ]);
        }

        $user->forceFill(['password' => $this->password, 'remember_token' => null])->save();
        $user->tokens()->delete(); // jetons d'API éventuels
        session()->forget(self::CLE_SESSION);

        $notifications->motDePasseModifie($user);

        Auth::login($user, remember: true);
        session()->regenerate();
        session()->flash('toast', 'Mot de passe modifié. Bienvenue à nouveau !');

        return $this->redirect($user->accueil());
    }

    /** « Je n'ai plus accès à ce numéro » / retour au formulaire. */
    public function afficher(string $etape): void
    {
        $this->resetErrorBag();

        $this->etape = match (true) {
            $etape === 'support' => 'support',
            $etape === 'code' && $this->telephone !== '' => 'code',
            default => 'telephone',
        };
    }

    public function render()
    {
        $support = array_filter(config('bookspace.support', []));
        $numero = $this->telephone !== '' ? $this->telephone : 'mon numéro';
        $message = "Bonjour, je n'ai plus accès au numéro {$numero} de mon compte BookSpace et je souhaite le récupérer. "
            . 'Mon nom complet : ';

        return view('livewire.auth.mot-de-passe-oublie', [
            'validite' => CodeVerificationService::VALIDITE_MINUTES,
            'attente' => max(0, $this->renvoiPossibleA - now()->timestamp),
            'support' => $support,
            'whatsapp' => isset($support['whatsapp']) ? 'https://wa.me/' . preg_replace('/\D+/', '', $support['whatsapp']) . '?text=' . rawurlencode($message) : null,
            'courriel' => isset($support['email'])
                ? 'mailto:' . $support['email'] . '?subject=' . rawurlencode('Récupération de mon compte BookSpace') . '&body=' . rawurlencode($message)
                : null,
        ]);
    }

    /** Envoie un code (si le compte existe) et arme le délai de renvoi. */
    private function expedier(CodeVerificationService $codes): void
    {
        $cle = 'mot-de-passe-oublie:' . request()->ip();
        if (RateLimiter::tooManyAttempts($cle, 10)) {
            throw ValidationException::withMessages([
                $this->etape === 'code' ? 'code' : 'telephone' => 'Trop de demandes. Réessayez dans une heure.',
            ]);
        }
        RateLimiter::hit($cle, 3600);

        if ($user = $this->compte()) {
            $codes->envoyer($user, 'reinitialisation', $user->telephone, $this->etape === 'code' ? 'code' : 'telephone');
        }

        $this->renvoiPossibleA = now()->addSeconds(self::DELAI_RENVOI)->timestamp;
    }

    /** Compte actif correspondant au numéro saisi, s'il existe. */
    private function compte(): ?User
    {
        return $this->telephone === '' ? null : User::whereIn('telephone', User::variantesTelephone($this->telephone))->where('actif', true)->first();
    }
}
