<?php

namespace App\Livewire\Messages;

use App\Livewire\Concerns\DansLeProfil;
use App\Models\Conversation;
use App\Models\Reservation;
use App\Services\MessagerieService;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Fil d'une conversation client <-> gérant : lecture et réponse. Les nouveaux
 * messages apparaissent tout seuls (sondage toutes les 10 s).
 */
class Fil extends Component
{
    use DansLeProfil;

    public Conversation $conversation;

    /** Réservation à laquelle le prochain message fait référence (ouverture depuis une réservation). */
    #[Url(as: 'reservation')]
    public ?int $reservationId = null;

    public string $contenu = '';

    /** Nombre de messages affichés au dernier rendu (défilement vers les nouveaux). */
    public int $vus = 0;

    public function mount(Conversation $conversation): void
    {
        abort_unless($conversation->concerne(auth()->user()), 403);

        $this->conversation = $conversation;
    }

    public function envoyer(MessagerieService $messagerie): void
    {
        $this->validate(
            ['contenu' => ['required', 'string', 'max:' . MessagerieService::LONGUEUR_MAX]],
            ['contenu.required' => 'Écrivez un message.'],
        );

        $messagerie->envoyer($this->conversation, auth()->user(), $this->contenu, $this->reservationId);

        // La référence à la réservation ne concerne que le premier message.
        $this->reset('contenu', 'reservationId');
    }

    public function retirerReference(): void
    {
        $this->reservationId = null;
    }

    public function render()
    {
        $user = auth()->user();
        $messagerie = app(MessagerieService::class);

        // Ouvrir le fil (et chaque rafraîchissement) vaut lecture.
        $messagerie->marquerLus($this->conversation, $user);
        $this->dispatch('compteur', type: 'messages', nombre: $messagerie->nonLus($user));

        $messages = $this->conversation->messages()
            ->with('reservation.espace:id,nom')
            ->latest('id')->limit(200)->get()->reverse()->values();

        if ($messages->count() > $this->vus) {
            $this->dispatch('fil-a-jour');
        }
        $this->vus = $messages->count();

        $client = $user->estClient();
        $interlocuteur = $this->conversation->interlocuteur($user);

        return view('livewire.messages.fil', [
            'user' => $user,
            'messages' => $messages,
            'interlocuteur' => $interlocuteur,
            'nom' => $client
                ? ($this->conversation->gerant?->raison_sociale ?? 'Gérant')
                : ($interlocuteur?->name ?? 'Client'),
            'reference' => $this->reservationId
                ? Reservation::with('espace:id,nom')
                    ->where('client_id', $this->conversation->client_id)
                    ->where('gerant_id', $this->conversation->gerant_id)
                    ->find($this->reservationId)
                : null,
            'longueurMax' => MessagerieService::LONGUEUR_MAX,
        ])
            // Client : plein écran (pas de barre de navigation sous le champ de saisie).
            ->layout($client ? 'components.layouts.ecran' : $this->coque())
            ->title('Messages · ' . ($client ? ($this->conversation->gerant?->raison_sociale ?? 'Gérant') : ($interlocuteur?->name ?? 'Client')));
    }
}
