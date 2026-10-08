<div class="flex flex-1 flex-col">
    <div class="bg-brand-dark px-5 pb-[18px] pt-5">
        <h1 class="font-display text-xl font-bold text-white">Mes réservations</h1>
    </div>

    <div class="flex gap-2 overflow-x-auto border-b border-slate-100 bg-white px-5 pb-2 pt-3.5">
        @foreach ($filtres as $id => $libelle)
            <button type="button" wire:click="filtrer('{{ $id }}')" @class(['puce', 'puce-active' => $statut === $id])
                    aria-pressed="{{ $statut === $id ? 'true' : 'false' }}">{{ $libelle }}</button>
        @endforeach
    </div>

    <div class="flex-1 px-5 pb-6 pt-4" wire:loading.class="opacity-50" wire:target="filtrer">
        @forelse ($reservations as $r)
            <a href="/reservations/{{ $r->id }}{{ $r->statut === 'en_attente' ? '/paiement' : '' }}" wire:key="resa-{{ $r->id }}"
               class="mb-3 block rounded-2xl bg-white p-4 shadow-carte-lg">
                <div class="mb-2 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="truncate text-[15px] font-bold text-brand-dark">{{ $r->espace?->nom ?? 'Espace' }}</div>
                        <div class="text-xs text-slate-400">{{ $r->reference }}</div>
                    </div>
                    <x-badge-statut :statut="$r->statut" />
                </div>
                <div class="mb-1 flex items-center gap-1.5 text-xs text-slate-500">
                    <x-icone nom="calendrier" taille="16" class="text-slate-400" />{{ date_longue($r->date_debut) }}
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500">{{ plage_horaire($r->date_debut, $r->date_fin) }}</span>
                    <span class="text-[13px] font-bold text-brand">{{ fcfa($r->prix_total) }}</span>
                </div>
                @if ($r->statut === 'en_attente')
                    <div class="mt-2.5 text-xs font-semibold text-attente">→ Payer l'acompte pour confirmer</div>
                @elseif ($r->statut === 'terminee' && ! $r->evaluation)
                    <div class="mt-2.5 flex items-center gap-1.5 text-xs font-bold text-brand">
                        <span class="text-[15px] text-gold">★</span> Donnez votre avis sur cet espace
                    </div>
                @elseif ($r->evaluation)
                    <div class="mt-2.5 text-xs text-slate-400"><span class="text-gold">★</span> Avis publié — merci !</div>
                @endif
            </a>
        @empty
            <x-vide titre="Aucune réservation" sous-titre="Réservez un espace depuis l'accueil." />
        @endforelse

        @if ($reste)
            <button type="button" wire:click="voirPlus"
                    class="mt-2 w-full rounded-xl border-[1.5px] border-slate-200 bg-white px-4 py-3 text-[13px] font-bold text-brand">
                Voir plus de réservations
            </button>
        @endif
    </div>
</div>
