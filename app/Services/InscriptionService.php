<?php

namespace App\Services;

use App\Models\Gerant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Création d'un compte client ou gérant (TDR §5.1). Un gérant reçoit un profil
 * `en_attente`, à valider par l'administrateur avant de publier un espace.
 */
class InscriptionService
{
    /** Règles de validation communes à l'API et au formulaire web. */
    public static function regles(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'telephone' => ['required', 'string', 'max:30', function (string $attribut, mixed $valeur, \Closure $echec) {
                // Y compris un compte enregistré sans indicatif (« 70000001 » = « +22670000001 »).
                if (User::whereIn('telephone', User::variantesTelephone((string) $valeur))->exists()) {
                    $echec('Ce numéro de téléphone est déjà associé à un compte.');
                }
            }],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['client', 'gerant'])],
            'raison_sociale' => ['required_if:role,gerant', 'nullable', 'string', 'max:255'],
        ];
    }

    public function inscrire(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'telephone' => User::normaliserTelephone($data['telephone']),
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'role' => $data['role'],
            ]);

            if ($data['role'] === 'gerant') {
                Gerant::create([
                    'user_id' => $user->id,
                    'raison_sociale' => $data['raison_sociale'],
                    'telephone_pro' => User::normaliserTelephone($data['telephone']),
                    'statut_validation' => 'en_attente',
                ]);
            }

            return $user;
        });
    }
}
