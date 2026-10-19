<?php

namespace App\Livewire\Profil;

use App\Livewire\Concerns\DansLeProfil;
use App\Models\Gerant;
use App\Models\User;
use App\Services\CodeVerificationService;
use App\Services\ImageConversionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Profil > Mon compte : photo, informations personnelles, numéro de téléphone
 * (vérifié par SMS, c'est l'identifiant de connexion) et, pour un gérant,
 * coordonnées de versement de ses revenus.
 */
#[Title('Mon compte')]
class Compte extends Component
{
    use DansLeProfil, WithFileUploads;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $photo = null;

    // ─── Informations ────────────────────────────────────────────────────────
    public string $name = '';

    public string $email = '';

    public string $raison_sociale = '';

    public string $telephone_pro = '';

    // ─── Changement de numéro ────────────────────────────────────────────────
    public string $nouveauTelephone = '';

    public string $motDePasseTelephone = '';

    public string $codeTelephone = '';

    public bool $codeEnvoye = false;

    // ─── Versement des revenus (gérant) ──────────────────────────────────────
    public string $momo_operateur = '';

    public string $momo_numero = '';

    public string $momo_titulaire = '';

    /** Section qui vient d'être enregistrée (retour visuel). */
    public ?string $enregistre = null;

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = (string) $user->email;

        if ($gerant = $user->gerant) {
            $this->raison_sociale = $gerant->raison_sociale;
            $this->telephone_pro = (string) $gerant->telephone_pro;
            $this->momo_operateur = (string) $gerant->momo_operateur;
            $this->momo_numero = (string) $gerant->momo_numero;
            $this->momo_titulaire = (string) $gerant->momo_titulaire;
        }
    }

    // ─── Photo ───────────────────────────────────────────────────────────────

    /** Enregistrée dès la sélection du fichier, convertie en WebP. */
    public function updatedPhoto(): void
    {
        try {
            $this->validate(['photo' => ['image', 'max:5120']], [
                'photo.image' => 'Choisissez une image (JPEG, PNG ou WebP).',
                'photo.max' => 'La photo doit faire 5 Mo au maximum.',
            ]);

            $user = auth()->user();
            $disque = Storage::disk(config('bookspace.photos_disk', 'public'));
            $images = app(ImageConversionService::class);
            $chemin = "avatars/{$user->id}/" . Str::random(32) . '.' . $images->extension();

            $disque->put($chemin, $images->versWebp($this->photo));

            if ($user->avatar) {
                $disque->delete($user->avatar);
            }
            $user->update(['avatar' => $chemin]);
            $this->enregistre = 'photo';
        } finally {
            $this->photo = null;
        }
    }

    public function supprimerPhoto(): void
    {
        $user = auth()->user();

        if ($user->avatar) {
            Storage::disk(config('bookspace.photos_disk', 'public'))->delete($user->avatar);
            $user->update(['avatar' => null]);
        }
        $this->enregistre = 'photo';
    }

    // ─── Informations ────────────────────────────────────────────────────────

    public function enregistrerInfos(): void
    {
        $user = auth()->user();
        $this->name = trim($this->name);
        $this->email = trim($this->email);

        $regles = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ];
        if ($user->estGerant()) {
            $this->raison_sociale = trim($this->raison_sociale);
            $this->telephone_pro = User::normaliserTelephone($this->telephone_pro);
            $regles += [
                'raison_sociale' => ['required', 'string', 'max:255'],
                'telephone_pro' => ['nullable', 'string', 'max:30'],
            ];
        }

        $data = $this->validate($regles, [], ['telephone_pro' => 'téléphone professionnel']);

        $user->update(['name' => $data['name'], 'email' => $data['email'] ?: null]);
        $user->gerant?->update([
            'raison_sociale' => $data['raison_sociale'],
            'telephone_pro' => $data['telephone_pro'] ?: null,
        ]);

        $this->enregistre = 'infos';
    }

    // ─── Numéro de téléphone (identifiant de connexion) ──────────────────────

    public function demanderCodeTelephone(CodeVerificationService $codes): void
    {
        $user = auth()->user();
        $this->nouveauTelephone = User::normaliserTelephone($this->nouveauTelephone);

        $this->validate([
            'nouveauTelephone' => [
                'required', 'string', 'max:30', 'regex:/^\+?\d{8,15}$/',
                Rule::notIn([$user->telephone]),
                function (string $attribut, mixed $valeur, \Closure $echec) use ($user) {
                    if (User::whereIn('telephone', User::variantesTelephone((string) $valeur))->whereKeyNot($user->id)->exists()) {
                        $echec('Ce numéro est déjà utilisé par un autre compte.');
                    }
                },
            ],
            'motDePasseTelephone' => ['required', 'string'],
        ], [
            'nouveauTelephone.regex' => 'Numéro invalide. Exemple : +22670000000.',
            'nouveauTelephone.not_in' => "C'est déjà votre numéro actuel.",
        ], [
            'nouveauTelephone' => 'nouveau numéro', 'motDePasseTelephone' => 'mot de passe',
        ]);

        if (! Hash::check($this->motDePasseTelephone, $user->password)) {
            throw ValidationException::withMessages(['motDePasseTelephone' => 'Mot de passe incorrect.']);
        }

        $codes->envoyer($user, 'changement_telephone', $this->nouveauTelephone, 'nouveauTelephone');

        $this->reset('motDePasseTelephone', 'codeTelephone');
        $this->codeEnvoye = true;
    }

    public function confirmerTelephone(CodeVerificationService $codes): void
    {
        $user = auth()->user();
        $this->validate(['codeTelephone' => ['required', 'digits:6']], ['codeTelephone.digits' => 'Le code comporte 6 chiffres.']);

        $verification = $codes->verifier($user, 'changement_telephone', $this->codeTelephone, 'codeTelephone');

        // Le numéro a pu être pris entre l'envoi du code et sa saisie.
        if (User::where('telephone', $verification->telephone)->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages(['codeTelephone' => 'Ce numéro est déjà utilisé par un autre compte.']);
        }

        $user->update(['telephone' => $verification->telephone]);

        $this->annulerTelephone();
        $this->enregistre = 'telephone';
    }

    public function annulerTelephone(): void
    {
        $this->reset('nouveauTelephone', 'motDePasseTelephone', 'codeTelephone', 'codeEnvoye');
        $this->resetErrorBag(['nouveauTelephone', 'motDePasseTelephone', 'codeTelephone']);
    }

    // ─── Versement des revenus (gérant) ──────────────────────────────────────

    public function enregistrerVersement(): void
    {
        $gerant = auth()->user()->gerant;
        abort_unless($gerant, 403);

        $this->momo_numero = User::normaliserTelephone($this->momo_numero);
        $this->momo_titulaire = trim($this->momo_titulaire);

        $data = $this->validate([
            'momo_operateur' => ['required', Rule::in(array_keys(Gerant::OPERATEURS))],
            'momo_numero' => ['required', 'string', 'regex:/^\+?\d{8,15}$/'],
            'momo_titulaire' => ['required', 'string', 'max:255'],
        ], [
            'momo_operateur.required' => "Choisissez l'opérateur.",
            'momo_numero.regex' => 'Numéro invalide. Exemple : +22670000000.',
        ], [
            'momo_numero' => 'numéro Mobile Money', 'momo_titulaire' => 'titulaire du compte',
        ]);

        $gerant->update($data);
        $this->enregistre = 'versement';
    }

    public function render()
    {
        return view('livewire.profil.compte', [
            'user' => auth()->user()->loadMissing('gerant'),
            'operateurs' => Gerant::OPERATEURS,
            'validite' => CodeVerificationService::VALIDITE_MINUTES,
        ])->layout($this->coque());
    }
}
