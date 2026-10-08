<div>
    <h1 class="titre-page mb-4">Paramètres de la plateforme</h1>

    @forelse ($parametres as $p)
        <x-panneau wire:key="parametre-{{ $p->cle }}" class="mb-4">
            <form wire:submit="enregistrer('{{ $p->cle }}')">
                <label for="parametre-{{ $p->cle }}" class="block text-[13px] font-bold text-brand-dark">{{ $p->libelle ?? $p->cle }}</label>
                <div class="mb-2.5 text-[11px] text-slate-400">{{ $p->cle }}</div>

                @error("valeurs.{$p->cle}") <x-erreur :message="$message" /> @enderror

                <div class="flex flex-wrap items-center gap-2.5">
                    <input id="parametre-{{ $p->cle }}" type="text" inputmode="decimal" wire:model="valeurs.{{ $p->cle }}"
                           class="champ-compact w-full max-w-[200px] flex-1 rounded-[10px] px-3 py-2.5">
                    <button type="submit" wire:loading.attr="disabled" wire:target="enregistrer('{{ $p->cle }}')"
                            class="rounded-[10px] bg-brand px-4 py-2.5 text-[13px] font-semibold text-white">Enregistrer</button>
                    @if ($enregistre === $p->cle)
                        <span class="text-[13px] font-semibold text-green-600" role="status">✓ Enregistré</span>
                    @endif
                </div>
            </form>
        </x-panneau>
    @empty
        <x-vide titre="Aucun paramètre" sous-titre="Lancez le seeder pour créer les paramètres par défaut." />
    @endforelse
</div>
