<div>
    <h1 class="titre-page mb-4">Validations</h1>

    <div class="mb-4 flex gap-2" role="tablist">
        @foreach (['gerants' => 'Gérants', 'espaces' => 'Espaces'] as $cle => $libelle)
            <button type="button" role="tab" wire:click="afficher('{{ $cle }}')" aria-selected="{{ $onglet === $cle ? 'true' : 'false' }}" @class([
                'rounded-lg px-4 py-2 text-[13px] font-semibold',
                'bg-brand text-white' => $onglet === $cle,
                'bg-white text-slate-600' => $onglet !== $cle,
            ])>
                {{ $libelle }}@if ($compteurs[$cle] > 0) ({{ $compteurs[$cle] }})@endif
            </button>
        @endforeach
    </div>

    @if ($onglet === 'gerants')
        @forelse ($gerants as $g)
            <x-panneau wire:key="gerant-{{ $g->id }}" class="mb-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-[15px] font-bold text-brand-dark">{{ $g->raison_sociale }}</div>
                        <div class="text-[13px] text-slate-500">
                            {{ $g->user?->name }} · {{ $g->user?->telephone }}
                            @if ($g->user?->email) · {{ $g->user->email }} @endif
                            @if ($g->ville) · {{ $g->ville }} @endif
                        </div>
                        @if ($g->momo_numero)
                            <div class="text-xs text-slate-400">
                                Versements : {{ \App\Models\Gerant::OPERATEURS[$g->momo_operateur] ?? $g->momo_operateur }} · {{ $g->momo_numero }} ({{ $g->momo_titulaire }})
                            </div>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button type="button" wire:click="validerGerant({{ $g->id }})" wire:loading.attr="disabled"
                                class="rounded-lg bg-green-600 px-3.5 py-2 text-[13px] font-semibold text-white">Valider</button>
                        <button type="button" class="rounded-lg bg-red-600 px-3.5 py-2 text-[13px] font-semibold text-white"
                                x-data @click="const motif = prompt('Motif du rejet :'); if (motif) $wire.rejeterGerant({{ $g->id }}, motif)">Rejeter</button>
                    </div>
                </div>
            </x-panneau>
        @empty
            <x-vide titre="Aucun gérant en attente" />
        @endforelse
    @else
        @forelse ($espaces as $e)
            <x-panneau wire:key="espace-{{ $e->id }}" class="mb-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="text-[15px] font-bold text-brand-dark">{{ $e->nom }}</div>
                        <div class="text-[13px] text-slate-500">
                            {{ libelle_type($e->type) }} · {{ $e->adresse }}, {{ $e->ville }} · {{ fcfa($e->tarif_horaire) }}/h
                            @if ($e->gerant) · {{ $e->gerant->raison_sociale }} @endif
                        </div>
                        @if ($e->description)
                            <div class="mt-1 text-[13px] text-slate-400">{{ $e->description }}</div>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button type="button" wire:click="validerEspace({{ $e->id }})" wire:loading.attr="disabled"
                                class="rounded-lg bg-green-600 px-3.5 py-2 text-[13px] font-semibold text-white">Valider</button>
                        <button type="button" class="rounded-lg bg-red-600 px-3.5 py-2 text-[13px] font-semibold text-white"
                                x-data @click="const motif = prompt('Motif du rejet :'); if (motif) $wire.rejeterEspace({{ $e->id }}, motif)">Rejeter</button>
                    </div>
                </div>
            </x-panneau>
        @empty
            <x-vide titre="Aucun espace en attente" />
        @endforelse

        @if ($publies->isNotEmpty())
            <h2 class="titre-section mb-3 mt-6">Espaces publiés</h2>
            <x-panneau class="p-0!">
                @foreach ($publies as $e)
                    <div wire:key="publie-{{ $e->id }}" class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 last:border-b-0">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-slate-800">{{ $e->nom }}</div>
                            <div class="text-xs text-slate-400">{{ libelle_type($e->type) }} · {{ $e->ville }} · {{ $e->gerant?->raison_sociale }}</div>
                        </div>
                        <button type="button" class="rounded-lg bg-[#FEF0D6] px-3 py-1.5 text-xs font-semibold text-[#854D0E]"
                                x-data @click="const motif = prompt('Motif de la suspension :'); if (motif) $wire.suspendreEspace({{ $e->id }}, motif)">Suspendre</button>
                    </div>
                @endforeach
            </x-panneau>
        @endif
    @endif
</div>
