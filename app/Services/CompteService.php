<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Opérations sur le compte de l'utilisateur (Profil > Mon compte / Sécurité).
 */
class CompteService
{
    /**
     * « Supprimer mon compte » : les données personnelles sont effacées et le
     * compte désactivé, mais les réservations, paiements et avis sont conservés
     * (comptabilité des gérants, obligations légales — cf. politique de
     * confidentialité). Une suppression réelle de la ligne `users` effacerait
     * ces historiques en cascade.
     *
     * @throws ValidationException s'il reste une réservation à venir
     */
    public function supprimer(User $user): void
    {
        abort_if($user->estAdmin(), 403, 'Un compte administrateur ne peut pas être supprimé depuis l’application.');

        if ($this->reservationsAVenir($user) > 0) {
            throw ValidationException::withMessages([
                'suppression' => $user->estGerant()
                    ? 'Des réservations à venir sont prévues dans vos espaces. Honorez-les ou annulez-les avant de supprimer votre compte.'
                    : 'Vous avez une réservation à venir. Annulez-la ou attendez qu’elle soit passée avant de supprimer votre compte.',
            ]);
        }

        DB::transaction(function () use ($user) {
            if ($user->avatar) {
                Storage::disk(config('bookspace.photos_disk', 'public'))->delete($user->avatar);
            }

            if ($gerant = $user->gerant) {
                // Les espaces disparaissent du catalogue (suppression douce).
                $gerant->espaces()->get()->each->delete();
                $gerant->update([
                    'raison_sociale' => 'Gérant supprimé',
                    'telephone_pro' => null,
                    'momo_operateur' => null,
                    'momo_numero' => null,
                    'momo_titulaire' => null,
                ]);
            }

            $user->favoris()->detach();
            $user->tokens()->delete();
            $user->forceFill([
                'name' => 'Compte supprimé',
                'telephone' => "supprime-{$user->id}-" . Str::lower(Str::random(6)),
                'email' => null,
                'avatar' => null,
                'password' => Hash::make(Str::random(40)),
                'remember_token' => null,
                'actif' => false,
                'notif_sms' => false,
                'notif_rappels' => false,
            ])->save();

            AuditEvent::create([
                'auteur_id' => $user->id,
                'sujet_type' => User::class,
                'sujet_id' => $user->id,
                'action' => 'compte_supprime',
                'ip' => request()->ip(),
                'created_at' => now(),
            ]);
        });
    }

    /** Réservations encore actives et à venir (côté client, ou dans les espaces du gérant). */
    public function reservationsAVenir(User $user): int
    {
        return Reservation::query()
            ->when(
                $user->estGerant(),
                fn ($q) => $q->where('gerant_id', $user->gerant?->id ?? 0),
                fn ($q) => $q->where('client_id', $user->id),
            )
            ->whereIn('statut', ['en_attente', 'confirmee'])
            ->where('date_fin', '>', now())
            ->count();
    }
}
