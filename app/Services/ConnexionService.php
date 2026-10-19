<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Vérification des identifiants (numéro + mot de passe), commune à la
 * connexion web et à l'API. Deux limites contre la force brute :
 * - par numéro et adresse IP : 5 échecs par minute ;
 * - par numéro, toutes adresses confondues : 20 échecs par heure (attaque
 *   répartie sur plusieurs adresses IP).
 */
class ConnexionService
{
    private const ECHECS_PAR_MINUTE = 5;

    private const ECHECS_PAR_HEURE = 20;

    /** @throws ValidationException identifiants incorrects, compte désactivé ou trop de tentatives */
    public function verifier(string $telephoneSaisi, string $password, string $ip): User
    {
        $telephone = User::normaliserTelephone($telephoneSaisi);
        $cleIp = "connexion:{$telephone}|{$ip}";
        $cleNumero = "connexion-numero:{$telephone}";

        foreach ([$cleIp => self::ECHECS_PAR_MINUTE, $cleNumero => self::ECHECS_PAR_HEURE] as $cle => $max) {
            if (RateLimiter::tooManyAttempts($cle, $max)) {
                $secondes = RateLimiter::availableIn($cle);

                throw ValidationException::withMessages([
                    'telephone' => 'Trop de tentatives. Réessayez dans '
                        . ($secondes > 120 ? ceil($secondes / 60) . ' minutes.' : "{$secondes} secondes."),
                ]);
            }
        }

        // Numéro normalisé, ou formes des comptes créés avant la normalisation.
        $user = User::whereIn('telephone', User::variantesTelephone($telephoneSaisi))->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($cleIp, 60);
            RateLimiter::hit($cleNumero, 3600);

            throw ValidationException::withMessages(['telephone' => 'Numéro ou mot de passe incorrect.']);
        }

        if (! $user->actif) {
            throw ValidationException::withMessages(['telephone' => 'Compte désactivé.']);
        }

        RateLimiter::clear($cleIp);
        RateLimiter::clear($cleNumero);
        $user->forceFill(['derniere_connexion_at' => now()])->save();

        return $user;
    }
}
