<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Messagerie client <-> gérant. Un échange n'est possible qu'entre un client
 * et le gérant d'un espace qu'il a réservé (pas de messages non sollicités).
 */
class MessagerieService
{
    public const LONGUEUR_MAX = 2000;

    /** Conversation liée à une réservation (créée au premier besoin). */
    public function pourReservation(Reservation $reservation, User $user): Conversation
    {
        abort_if($reservation->client_id === null, 422, 'Cette réservation a été saisie sans compte client.');

        $autorise = $user->estGerant()
            ? $reservation->gerant_id === $user->gerant?->id
            : $reservation->client_id === $user->id;
        abort_unless($autorise, 403);

        return Conversation::firstOrCreate([
            'client_id' => $reservation->client_id,
            'gerant_id' => $reservation->gerant_id,
        ]);
    }

    public function envoyer(Conversation $conversation, User $auteur, string $contenu, ?int $reservationId = null): Message
    {
        abort_unless($conversation->concerne($auteur), 403);

        $contenu = trim($contenu);
        if ($contenu === '') {
            throw ValidationException::withMessages(['contenu' => 'Écrivez un message.']);
        }

        $cle = "message:{$auteur->id}";
        if (RateLimiter::tooManyAttempts($cle, 20)) {
            throw ValidationException::withMessages(['contenu' => 'Trop de messages envoyés. Patientez une minute.']);
        }
        RateLimiter::hit($cle, 60);

        // La réservation citée doit appartenir au même couple client / gérant.
        $reservationId = $reservationId && Reservation::whereKey($reservationId)
            ->where('client_id', $conversation->client_id)
            ->where('gerant_id', $conversation->gerant_id)
            ->exists() ? $reservationId : null;

        $message = $conversation->messages()->create([
            'auteur_id' => $auteur->id,
            'reservation_id' => $reservationId,
            'contenu' => mb_substr($contenu, 0, self::LONGUEUR_MAX),
        ]);
        $conversation->update(['dernier_message_at' => $message->created_at]);

        return $message;
    }

    /** Marque comme lus les messages reçus par `$user` dans la conversation. */
    public function marquerLus(Conversation $conversation, User $user): void
    {
        $conversation->messages()
            ->where('auteur_id', '!=', $user->id)
            ->whereNull('lu_at')
            ->update(['lu_at' => now()]);
    }

    /** Nombre de messages reçus non lus (pastille de navigation). */
    public function nonLus(User $user): int
    {
        if (! $user->estClient() && ! $user->estGerant()) {
            return 0;
        }

        return Message::whereNull('lu_at')
            ->where('auteur_id', '!=', $user->id)
            ->whereHas('conversation', fn ($q) => $q->de($user))
            ->count();
    }
}
