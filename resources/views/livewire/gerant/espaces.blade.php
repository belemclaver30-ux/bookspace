<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="titre-page">Mes espaces</h1>
        @if ($valide)
            <button type="button" wire:click="basculerCreation" class="rounded-[10px] bg-brand px-4 py-[9px] text-[13px] font-semibold text-white">
                {{ $creation ? 'Fermer' : '+ Ajouter un espace' }}
            </button>
        @endif
    </div>

    @unless ($valide)
        <div class="mb-4 rounded-xl bg-yellow-100 p-3.5 text-[13px] text-[#854D0E]">
            Compte gérant non validé : la création d'espace sera disponible après validation.
        </div>
    @endunless

    @if (session('succes'))
        <div class="mb-4 rounded-xl bg-green-100 p-3.5 text-[13px] font-semibold text-green-700" role="status">{{ session('succes') }}</div>
    @endif

    @if ($creation)
        <form wire:submit="creer" class="mb-4 max-w-[460px] rounded-[14px] bg-white p-5 shadow-carte">
            <div class="titre-section mb-3">Nouvel espace</div>

            <div class="mb-3.5 flex gap-2">
                @foreach (['salle_repetition', 'terrain_foot'] as $t)
                    <button type="button" wire:click="$set('type', '{{ $t }}')" aria-pressed="{{ $type === $t ? 'true' : 'false' }}" @class([
                        'flex-1 rounded-[10px] p-2.5 text-xs font-semibold',
                        'border-2 border-brand bg-brand-50 text-brand' => $type === $t,
                        'border-[1.5px] border-slate-200 bg-white text-slate-500' => $type !== $t,
                    ])>{{ libelle_type($t) }}</button>
                @endforeach
            </div>

            <x-erreur />

            <label class="mb-4 block">
                <span class="etiquette">NOM</span>
                <input type="text" wire:model="nom" class="champ" required maxlength="255">
            </label>
            <label class="mb-4 block">
                <span class="etiquette">ADRESSE</span>
                <input type="text" wire:model="adresse" class="champ" required maxlength="255">
            </label>
            <label class="mb-4 block">
                <span class="etiquette">VILLE</span>
                <input type="text" wire:model="ville" class="champ" required maxlength="255">
            </label>
            <label class="mb-4 block">
                <span class="etiquette">TARIF HORAIRE (FCFA)</span>
                <input type="number" wire:model="tarif_horaire" class="champ" required min="0" step="any" inputmode="numeric">
            </label>
            <label class="mb-4 block">
                <span class="etiquette">ÉQUIPEMENTS (séparés par des virgules)</span>
                <input type="text" wire:model="equipements" class="champ" placeholder="WiFi, Climatisation, Parking">
            </label>
            <label class="mb-4 block">
                <span class="etiquette">DESCRIPTION</span>
                <textarea wire:model="description" rows="3" class="champ resize-y"></textarea>
            </label>

            <button type="submit" class="btn-primaire" wire:loading.attr="disabled" wire:target="creer">
                <span wire:loading.remove wire:target="creer">Créer (soumis à validation)</span>
                <span wire:loading wire:target="creer">Création…</span>
            </button>
        </form>
    @endif

    @if ($espaces->isEmpty() && ! $creation)
        <x-vide titre="Aucun espace" :sous-titre="$valide ? 'Ajoutez votre premier espace.' : null" />
    @endif

    <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-3.5">
        @foreach ($espaces as $e)
            <div wire:key="espace-{{ $e->id }}" class="carte">
                <a href="/gerant/espaces/{{ $e->id }}" class="block">
                    <div class="mb-1.5 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate text-[15px] font-bold text-brand-dark">{{ $e->nom }}</div>
                            <div class="text-xs text-slate-400">{{ libelle_type($e->type) }} · {{ $e->ville }}</div>
                        </div>
                        <x-badge-statut :statut="$e->statut" />
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-[13px] text-slate-500">{{ fcfa($e->tarif_horaire) }}/h</span>
                        <span class="text-xs font-semibold text-brand">Gérer →</span>
                    </div>
                </a>
                @if (in_array($e->statut, ['rejete', 'suspendu'], true) && $e->motif_rejet)
                    <div class="mt-2 text-xs text-red-700">Motif : {{ $e->motif_rejet }}</div>
                @endif
                @if ($e->statut === 'rejete')
                    <button type="button" wire:click="soumettre({{ $e->id }})" class="mt-2.5 rounded-[10px] bg-brand-50 px-3 py-2 text-xs font-semibold text-brand">
                        Re-soumettre à validation
                    </button>
                @endif
            </div>
        @endforeach
    </div>
</div>
