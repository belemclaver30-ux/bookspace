@php $libelles = ['ouvert' => 'ouvert', 'en_cours' => 'en cours', 'resolu' => 'résolu', 'ferme' => 'fermé']; @endphp
<div>
    <h1 class="titre-page mb-4">Litiges</h1>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ($statuts as $id => $libelle)
            <button type="button" wire:click="filtrer('{{ $id }}')" aria-pressed="{{ $statut === $id ? 'true' : 'false' }}" @class([
                'rounded-lg px-3 py-1.5 text-xs font-semibold',
                'bg-brand text-white' => $statut === $id,
                'bg-white text-slate-600' => $statut !== $id,
            ])>{{ $libelle }}</button>
        @endforeach
    </div>

    @forelse ($litiges as $l)
        <x-panneau wire:key="litige-{{ $l->id }}" class="mb-4">
            <div class="flex flex-wrap justify-between gap-3">
                <div class="min-w-60 flex-1">
                    <div class="titre-section">{{ $l->sujet }}</div>
                    <div class="mb-1.5 text-xs text-slate-400">
                        {{ $l->reservation?->reference ?? '—' }} · ouvert par {{ $l->auteur?->name ?? '?' }} ·
                        {{ date_longue($l->created_at) }} · <b>{{ $libelles[$l->statut] ?? $l->statut }}</b>
                    </div>
                    <div class="text-[13px] text-slate-600">{{ $l->description }}</div>
                    @if ($l->resolution)
                        <div class="mt-1.5 text-[13px] text-green-600">Résolution : {{ $l->resolution }}</div>
                    @endif
                </div>
                @if (in_array($l->statut, ['ouvert', 'en_cours'], true))
                    <div class="flex flex-col gap-1.5" x-data="{
                        clore(statut) {
                            const note = prompt('Note de résolution :');
                            if (note !== null) $wire.traiter({{ $l->id }}, statut, note);
                        },
                    }">
                        @if ($l->statut === 'ouvert')
                            <button type="button" wire:click="traiter({{ $l->id }}, 'en_cours')" class="rounded-lg bg-yellow-600 px-3 py-1.5 text-xs font-semibold text-white">Prendre en charge</button>
                        @endif
                        <button type="button" @click="clore('resolu')" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white">Résolu</button>
                        <button type="button" @click="clore('ferme')" class="rounded-lg bg-slate-500 px-3 py-1.5 text-xs font-semibold text-white">Fermer</button>
                    </div>
                @endif
            </div>
        </x-panneau>
    @empty
        <x-vide titre="Aucun litige" />
    @endforelse
</div>
