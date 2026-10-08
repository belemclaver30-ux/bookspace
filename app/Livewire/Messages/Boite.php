<?php

namespace App\Livewire\Messages;

use App\Livewire\Concerns\DansLeProfil;
use App\Models\Conversation;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Boîte de réception : conversations du client avec ses gérants (ou du gérant avec ses clients). */
#[Title('Messages')]
class Boite extends Component
{
    use DansLeProfil;

    public function render()
    {
        $user = auth()->user();

        $conversations = Conversation::query()
            ->de($user)
            ->whereNotNull('dernier_message_at')
            ->with(['dernierMessage', 'client', 'gerant.user'])
            ->withCount(['messages as non_lus' => fn ($q) => $q->whereNull('lu_at')->where('auteur_id', '!=', $user->id)])
            ->orderByDesc('dernier_message_at')
            ->limit(100)
            ->get();

        return view('livewire.messages.boite', [
            'user' => $user,
            'conversations' => $conversations,
        ])->layout($this->coque());
    }
}
