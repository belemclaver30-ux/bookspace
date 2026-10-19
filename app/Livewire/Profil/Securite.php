<?php

namespace App\Livewire\Profil;

use App\Livewire\Concerns\DansLeProfil;
use App\Services\CompteService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Profil > Sécurité : changement de mot de passe, déconnexion des autres
 * appareils et suppression du compte.
 */
#[Title('Sécurité')]
class Securite extends Component
{
    use DansLeProfil;

    public string $actuel = '';

    public string $nouveau = '';

    public string $nouveau_confirmation = '';

    public string $motDePasseAppareils = '';

    public string $motDePasseSuppression = '';

    public bool $confirmeSuppression = false;

    public ?string $enregistre = null;

    public function changerMotDePasse(): void
    {
        $this->validate([
            'actuel' => ['required', 'string'],
            'nouveau' => ['required', 'string', 'min:8', 'confirmed', 'different:actuel'],
        ], [
            'nouveau.different' => 'Le nouveau mot de passe doit être différent de l’actuel.',
            'nouveau.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ], [
            'actuel' => 'mot de passe actuel', 'nouveau' => 'nouveau mot de passe',
        ]);

        $user = auth()->user();
        $this->verifierMotDePasse($this->actuel, 'actuel');

        $user->forceFill(['password' => $this->nouveau])->save();
        $user->tokens()->delete();
        app(NotificationService::class)->motDePasseModifie($user);

        // Les sessions des autres appareils, liées à l'ancien mot de passe, se
        // ferment d'elles-mêmes ; celle-ci est reconnectée avec le nouveau.
        Auth::login($user, remember: true);

        $this->reset('actuel', 'nouveau', 'nouveau_confirmation');
        $this->enregistre = 'mot_de_passe';
    }

    public function deconnecterAutresAppareils(): void
    {
        $this->validate(['motDePasseAppareils' => ['required', 'string']], [], ['motDePasseAppareils' => 'mot de passe']);
        $this->verifierMotDePasse($this->motDePasseAppareils, 'motDePasseAppareils');

        Auth::logoutOtherDevices($this->motDePasseAppareils);
        auth()->user()->tokens()->delete();

        $this->reset('motDePasseAppareils');
        $this->enregistre = 'appareils';
    }

    public function supprimerCompte(CompteService $comptes)
    {
        abort_if(auth()->user()->estAdmin(), 403);

        $this->validate([
            'motDePasseSuppression' => ['required', 'string'],
            'confirmeSuppression' => ['accepted'],
        ], [
            'confirmeSuppression.accepted' => 'Cochez la case pour confirmer la suppression.',
        ], [
            'motDePasseSuppression' => 'mot de passe',
        ]);
        $this->verifierMotDePasse($this->motDePasseSuppression, 'motDePasseSuppression');

        $comptes->supprimer(auth()->user());

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return $this->redirect('/');
    }

    public function render()
    {
        $user = auth()->user();
        $comptes = app(CompteService::class);

        return view('livewire.profil.securite', [
            'user' => $user,
            'aVenir' => $user->estAdmin() ? 0 : $comptes->reservationsAVenir($user),
        ])->layout($this->coque());
    }

    private function verifierMotDePasse(string $motDePasse, string $champ): void
    {
        if (! Hash::check($motDePasse, auth()->user()->password)) {
            throw ValidationException::withMessages([$champ => 'Mot de passe incorrect.']);
        }
    }
}
