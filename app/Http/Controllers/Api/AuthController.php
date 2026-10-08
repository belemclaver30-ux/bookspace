<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\InscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Inscription / connexion par numéro de téléphone (TDR §5.1).
 * Auth par jeton Sanctum : le client mobile stocke le token et l'envoie en
 * `Authorization: Bearer …`.
 */
class AuthController extends Controller
{
    public function register(Request $request, InscriptionService $inscriptions): JsonResponse
    {
        $user = $inscriptions->inscrire($request->validate(InscriptionService::regles()));

        return $this->reponseAvecToken($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telephone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('telephone', $data['telephone'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'telephone' => 'Numéro ou mot de passe incorrect.',
            ]);
        }

        if (! $user->actif) {
            throw ValidationException::withMessages(['telephone' => 'Compte désactivé.']);
        }

        $user->forceFill(['derniere_connexion_at' => now()])->save();

        return $this->reponseAvecToken($user);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->profil($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    private function reponseAvecToken(User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'data' => $this->profil($user),
        ], $status);
    }

    private function profil(User $user): array
    {
        $user->loadMissing('gerant');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'telephone' => $user->telephone,
            'email' => $user->email,
            'role' => $user->role,
            'avatar' => $user->avatar,
            'gerant' => $user->gerant ? [
                'id' => $user->gerant->id,
                'raison_sociale' => $user->gerant->raison_sociale,
                'statut_validation' => $user->gerant->statut_validation,
            ] : null,
        ];
    }
}
