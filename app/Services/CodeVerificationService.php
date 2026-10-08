<?php

namespace App\Services;

use App\Models\CodeVerification;
use App\Models\User;
use App\Services\Sms\PasserelleSms;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Codes de vérification à 6 chiffres envoyés par SMS (mot de passe oublié,
 * changement de numéro). Le code est haché, expire après quelques minutes et
 * n'accepte qu'un nombre limité d'essais. Ces SMS de sécurité partent même si
 * l'utilisateur a désactivé les SMS dans ses préférences.
 */
class CodeVerificationService
{
    public const VALIDITE_MINUTES = 10;

    public const ESSAIS_MAX = 5;

    /** Nombre maximal de codes envoyés à un même numéro par heure. */
    private const ENVOIS_PAR_HEURE = 3;

    public function __construct(private readonly PasserelleSms $sms) {}

    /**
     * Envoie un nouveau code (et invalide les précédents pour le même objet).
     *
     * @throws ValidationException si trop de codes ont déjà été demandés
     */
    public function envoyer(User $user, string $objet, string $telephone, string $champ = 'telephone'): void
    {
        $cle = "code-sms:{$telephone}";

        if (RateLimiter::tooManyAttempts($cle, self::ENVOIS_PAR_HEURE)) {
            $minutes = (int) ceil(RateLimiter::availableIn($cle) / 60);

            throw ValidationException::withMessages([
                $champ => "Trop de codes demandés pour ce numéro. Réessayez dans {$minutes} min.",
            ]);
        }
        RateLimiter::hit($cle, 3600);

        $code = (string) random_int(100000, 999999);

        CodeVerification::where('user_id', $user->id)->where('objet', $objet)->whereNull('utilise_at')->delete();
        CodeVerification::create([
            'user_id' => $user->id,
            'objet' => $objet,
            'telephone' => $telephone,
            'code_hash' => Hash::make($code),
            'expire_at' => now()->addMinutes(self::VALIDITE_MINUTES),
        ]);

        $motif = $objet === 'reinitialisation' ? 'réinitialiser votre mot de passe' : 'confirmer votre nouveau numéro';
        $this->sms->envoyer(
            $telephone,
            "BookSpace : votre code pour {$motif} est {$code}. Il expire dans " . self::VALIDITE_MINUTES
            . ' min. Ne le communiquez à personne.',
        );
    }

    /**
     * Vérifie le code saisi et le consomme s'il est correct.
     *
     * @throws ValidationException code invalide, expiré ou trop d'essais
     */
    public function verifier(User $user, string $objet, string $code, string $champ = 'code'): CodeVerification
    {
        $enCours = CodeVerification::where('user_id', $user->id)
            ->where('objet', $objet)
            ->whereNull('utilise_at')
            ->latest('id')
            ->first();

        if (! $enCours || $enCours->expire_at->isPast() || $enCours->tentatives >= self::ESSAIS_MAX) {
            throw ValidationException::withMessages([$champ => 'Ce code a expiré. Demandez-en un nouveau.']);
        }

        if (! Hash::check(preg_replace('/\D/', '', $code), $enCours->code_hash)) {
            $enCours->increment('tentatives');
            $restants = self::ESSAIS_MAX - $enCours->tentatives;

            throw ValidationException::withMessages([
                $champ => $restants > 0
                    ? "Code incorrect. Encore {$restants} essai" . ($restants > 1 ? 's' : '') . '.'
                    : 'Code incorrect. Demandez un nouveau code.',
            ]);
        }

        $enCours->update(['utilise_at' => now()]);

        return $enCours;
    }
}
