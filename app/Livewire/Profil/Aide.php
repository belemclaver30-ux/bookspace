<?php

namespace App\Livewire\Profil;

use App\Livewire\Concerns\DansLeProfil;
use App\Models\Litige;
use App\Models\Parametre;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Profil > Centre d'aide : questions fréquentes selon le rôle, contact du
 * support et signalement d'un problème sur une réservation. Un signalement
 * ouvre un litige, traité par l'administrateur (TDR §5.3).
 */
#[Title("Centre d'aide")]
class Aide extends Component
{
    use DansLeProfil;

    public string $reservationId = '';

    public string $sujet = '';

    public string $description = '';

    public bool $envoye = false;

    /** Relance la visite guidée : elle s'ouvre sur la page d'accueil du rôle. */
    public function revoirVisite()
    {
        $user = auth()->user();
        abort_if($user->estAdmin(), 403);

        $user->forceFill(['visite_terminee_at' => null])->save();

        return $this->redirect($user->accueil());
    }

    public function signaler(): void
    {
        $user = auth()->user();
        abort_if($user->estAdmin(), 403);

        $this->envoye = false;
        $this->sujet = trim($this->sujet);
        $this->description = trim($this->description);

        $data = $this->validate([
            'reservationId' => ['required', 'integer'],
            'sujet' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'reservationId.required' => 'Choisissez la réservation concernée.',
        ], [
            'sujet' => 'sujet', 'description' => 'description',
        ]);

        // Seules ses propres réservations (client) ou celles de ses espaces (gérant).
        $reservation = $this->reservations()->find($data['reservationId']);

        if (! $reservation) {
            throw ValidationException::withMessages(['reservationId' => 'Réservation introuvable.']);
        }

        $dejaOuvert = $reservation->litiges()
            ->where('ouvert_par', $user->id)
            ->whereIn('statut', ['ouvert', 'en_cours'])
            ->exists();

        if ($dejaOuvert) {
            throw ValidationException::withMessages([
                'reservationId' => 'Vous avez déjà un signalement en cours pour cette réservation.',
            ]);
        }

        $cle = "signalement:{$user->id}";

        if (RateLimiter::tooManyAttempts($cle, 5)) {
            throw ValidationException::withMessages([
                'sujet' => 'Trop de signalements envoyés. Réessayez plus tard ou contactez le support.',
            ]);
        }
        RateLimiter::hit($cle, 3600);

        $litige = $reservation->litiges()->create([
            'ouvert_par' => $user->id,
            'sujet' => $data['sujet'],
            'description' => $data['description'],
            'statut' => 'ouvert',
        ]);
        $litige->journaliser('litige_ouvert');

        $this->reset('reservationId', 'sujet', 'description');
        $this->envoye = true;
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.profil.aide', [
            'user' => $user,
            'questions' => $this->questions($user->role),
            'support' => array_filter(config('bookspace.support', [])),
            'reservations' => $user->estAdmin()
                ? collect()
                : $this->reservations()->with('espace:id,nom')->latest('date_debut')->limit(30)->get(),
            'signalements' => Litige::with('reservation.espace:id,nom')
                ->where('ouvert_par', $user->id)->latest()->limit(20)->get(),
        ])->layout($this->coque());
    }

    /** Réservations sur lesquelles l'utilisateur peut ouvrir un signalement. */
    private function reservations(): Builder
    {
        $user = auth()->user();

        return Reservation::query()->where(
            fn (Builder $q) => $user->estGerant()
                ? $q->where('gerant_id', $user->gerant()->value('id') ?? 0)
                : $q->where('client_id', $user->id),
        );
    }

    /**
     * Questions fréquentes du rôle. Les chiffres viennent des paramètres de la
     * plateforme : la réponse reste juste quand l'administrateur les modifie.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function questions(string $role): array
    {
        $acompte = heures_lisibles(Parametre::valeur('deposit_rate', config('bookspace.deposit_rate')));
        $commission = heures_lisibles(Parametre::valeur('commission_rate', config('bookspace.commission_rate')));
        $delaiPaiement = (int) config('bookspace.reservation_expiration_minutes', 30);
        $rappel = (int) config('bookspace.rappel_minutes_avant', 30);

        return match ($role) {
            'gerant' => [
                ['Comment publier un espace ?', "Votre compte gérant doit d'abord être validé par l'administrateur. Ensuite, dans « Mes espaces », ajoutez votre espace : il est soumis à validation, puis devient visible des clients. Ajoutez des photos, la localisation et vos horaires d'ouverture depuis « Gérer »."],
                ['Mon espace a été rejeté ou suspendu, que faire ?', "Le motif est affiché sur la fiche de l'espace. Corrigez ce qui est demandé puis utilisez « Re-soumettre à validation ». En cas de désaccord, signalez-le au support."],
                ['Comment définir mes disponibilités ?', "Dans la gestion de l'espace : cochez les jours d'ouverture et leurs horaires. Pour une fermeture ponctuelle (travaux, événement privé), ajoutez une « plage bloquée » : les clients ne pourront pas réserver sur cette période."],
                ['Un client a réservé par téléphone ou sur place, comment le noter ?', "Dans « Réservations », utilisez « + Ajouter une réservation ». Le créneau est bloqué immédiatement et aucune commission n'est prélevée, le paiement se faisant en dehors de l'application."],
                ['Comment traiter une demande d\'annulation ou de report ?', "Les demandes des clients arrivent dans « Demandes ». Si vous acceptez, la réservation est annulée ou déplacée automatiquement et le client est prévenu. Si vous refusez, vous pouvez lui laisser un message."],
                ['Quelle commission est prélevée ?', "La plateforme prélève {$commission} % de l'acompte payé en ligne par le client. L'acompte représente {$acompte} % du prix ; le reste vous est réglé sur place par le client."],
                ['Comment suis-je prévenu d\'une réservation ?', "Par une notification dans l'application et par SMS dès que l'acompte est payé, puis par un rappel environ {$rappel} minutes avant le créneau. Vous pouvez régler cela dans Profil > Préférences."],
            ],
            'admin' => [
                ['Comment valider un gérant ou un espace ?', "Dans « Validations » : les gérants et les espaces en attente y sont listés. Un rejet demande un motif, communiqué au gérant. Un espace déjà publié peut être suspendu depuis le même écran."],
                ['Comment traiter un signalement ?', "Les signalements des clients et des gérants arrivent dans « Litiges ». Prenez-le en charge, puis marquez-le résolu ou fermé avec une note : son auteur reçoit une notification."],
                ['Où modifier la commission et l\'acompte ?', "Dans « Paramètres ». Les nouvelles valeurs s'appliquent aux réservations créées ensuite ; les réservations existantes gardent les taux en vigueur au moment de leur création."],
            ],
            default => [
                ['Comment réserver un espace ?', "Choisissez un espace depuis l'accueil, puis « Réserver maintenant » : sélectionnez la date, la durée et un créneau libre. Votre réservation est confirmée dès que l'acompte est payé."],
                ['Comment se passe le paiement ?', "Vous payez en ligne un acompte de {$acompte} % par Mobile Money (Orange Money, Moov Money, Telecel Money) sur la page sécurisée de notre partenaire Yenga. Le reste est à régler sur place, directement au gérant."],
                ['Combien de temps ai-je pour payer l\'acompte ?', "{$delaiPaiement} minutes après avoir choisi votre créneau. Passé ce délai, la réservation est annulée automatiquement et le créneau redevient disponible."],
                ['J\'ai payé mais ma réservation n\'est pas confirmée', "La confirmation peut prendre quelques instants. Ouvrez la réservation depuis « Réservations » puis « J'ai terminé mon paiement, vérifier ». Si elle reste en attente alors que vous avez été débité, signalez-le ci-dessous en indiquant le numéro utilisé."],
                ['Comment annuler ou reporter une réservation ?', "Tant que l'acompte n'est pas payé, vous pouvez annuler directement. Une fois la réservation confirmée, ouvrez-la et envoyez une demande d'annulation ou de report : le gérant doit la valider, et vous êtes prévenu de sa réponse."],
                ['Comment laisser un avis ?', "Une fois votre créneau passé, la réservation devient « Terminée » : ouvrez-la pour noter l'espace et laisser un commentaire."],
                ['Comment me rendre sur place ?', "Sur la fiche de l'espace, onglet « Informations » : la carte indique son emplacement, « Calculer l'itinéraire » trace le trajet depuis votre position et « Ouvrir dans Maps » lance la navigation de votre téléphone."],
            ],
        };
    }
}
