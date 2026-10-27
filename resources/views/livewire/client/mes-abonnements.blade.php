<div class="flex flex-1 flex-col">
    <div class="flex items-center gap-3 bg-brand-dark px-5 pb-[18px] pt-5">
        <x-retour href="/reservations" />
        <h1 class="font-display text-xl font-bold text-white">Mes abonnements</h1>
    </div>

    <div class="flex-1 px-5 pb-6 pt-4">
        <x-erreur :message="$errors->first('abonnement')" />

        @forelse ($abonnements as $a)
            <div wire:key="abo-{{ $a->id }}" class="mb-3 rounded-2xl bg-white p-4 shadow-carte-lg">
                <a href="/abonnements/{{ $a->id }}/{{ $a->statut === 'en_attente' ? 'paiement' : 'confirmation' }}" class="block">
                    <div class="mb-2 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate text-[15px] font-bold text-brand-dark">{{ $a->espace?->nom ?? 'Espace' }}</div>
                            <div class="text-xs text-slate-400">{{ $a->reference }}</div>
                        </div>
                        <x-badge-statut :statut="$a->statut" />
                    </div>
                    <div class="mb-1 flex items-center gap-1.5 text-xs text-slate-500">
                        <x-icone nom="calendrier" taille="16" class="text-slate-400" />{{ $a->nombre_seances }} séances, une par semaine
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500">Acompte {{ heures_lisibles($a->taux_acompte) }}%</span>
                        <span class="text-[13px] font-bold text-brand">{{ fcfa($a->prix_total) }}</span>
                    </div>
                    @if ($a->statut === 'en_attente')
                        <div class="mt-2.5 text-xs font-semibold text-attente">→ Payer l'acompte pour confirmer</div>
                    @endif
                </a>
                @if ($a->statut === 'en_attente')
                    <button type="button" wire:click="annuler({{ $a->id }})" wire:confirm="Annuler cet abonnement ?"
                            class="mt-2.5 text-xs font-semibold text-red-600">Annuler</button>
                @endif
            </div>
        @empty
            <x-vide titre="Aucun abonnement" sous-titre="Souscrivez un pack de séances depuis la fiche d'un espace." />
        @endforelse
    </div>
</div>
