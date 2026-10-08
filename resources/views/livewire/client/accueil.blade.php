<div class="flex flex-1 flex-col">
    <div class="bg-brand-dark px-5 pb-5 pt-5">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <div class="text-xs font-medium text-white/55">Bonjour, {{ str(auth()->user()->name)->before(' ') }} 👋</div>
                <h1 class="mt-0.5 font-display text-xl font-bold text-white">Trouvez votre espace</h1>
            </div>
            <x-cloche />
        </div>
        <label class="flex items-center gap-2.5 rounded-[14px] bg-white px-3.5 py-3 text-slate-500">
            <x-icone nom="recherche" />
            <span class="sr-only">Chercher un espace</span>
            <input type="search" wire:model.live.debounce.400ms="q" placeholder="Chercher un espace..."
                   class="flex-1 border-none bg-transparent text-sm text-ink outline-none">
        </label>
    </div>

    <div class="border-b border-slate-100 bg-white px-5 pb-2 pt-4"
         x-data="{
            erreurGeo: '',
            async basculerProximite() {
                if ($wire.proche) { $wire.desactiverProximite(); return; }
                this.erreurGeo = '';
                try {
                    const p = await positionActuelle();
                    await $wire.activerProximite(p.lat, p.lng);
                } catch (e) {
                    this.erreurGeo = e.message;
                }
            },
         }">
        <div class="flex flex-wrap gap-2">
            @foreach ([['tous', 'Tous', null], ['salle_repetition', 'Salle de répétition', 'musique'], ['terrain_foot', 'Terrain de football', 'football']] as [$id, $libelle, $icone])
                <button type="button" wire:click="filtrer('{{ $id }}')" @class(['puce', 'puce-active' => $filtre === $id])
                        aria-pressed="{{ $filtre === $id ? 'true' : 'false' }}">
                    @if ($icone) <x-icone :nom="$icone" taille="16" /> @endif
                    {{ $libelle }}
                </button>
            @endforeach
            <button type="button" @click="basculerProximite()" @class(['puce', 'puce-active' => $proche])
                    aria-pressed="{{ $proche ? 'true' : 'false' }}">
                <x-icone nom="lieu" taille="13" />Près de moi
            </button>
        </div>
        <div x-cloak x-show="erreurGeo" x-text="erreurGeo" class="mt-1.5 text-[11px] text-red-600"></div>
    </div>

    <div class="flex-1 px-5 pb-6 pt-4">
        <div class="mb-3 text-xs font-semibold uppercase tracking-[0.5px] text-slate-400" wire:loading.class="opacity-50">
            {{ $total }} espace{{ $total > 1 ? 's' : '' }} trouvé{{ $total > 1 ? 's' : '' }}
        </div>

        @forelse ($espaces as $espace)
            <x-carte-espace :espace="$espace" wire:key="espace-{{ $espace->id }}" />
        @empty
            <x-vide titre="Aucun espace" sous-titre="Essayez d'élargir votre recherche." />
        @endforelse

        @if ($reste)
            <button type="button" wire:click="voirPlus" wire:loading.attr="disabled"
                    class="mt-2 w-full rounded-xl border-[1.5px] border-slate-200 bg-white px-4 py-3 text-[13px] font-bold text-brand">
                <span wire:loading.remove wire:target="voirPlus">Voir plus d'espaces</span>
                <span wire:loading wire:target="voirPlus">Chargement…</span>
            </button>
        @endif
    </div>
</div>
