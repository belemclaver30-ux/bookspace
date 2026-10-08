<div class="flex flex-1 flex-col">
    <x-entete-profil titre="Mes favoris" sous-titre="Vos espaces préférés, pour réserver en un geste" />

    <div class="px-5 pb-6 pt-4">
        @forelse ($espaces as $espace)
            <div wire:key="favori-{{ $espace->id }}" class="relative">
                <x-carte-espace :espace="$espace" />
                <button type="button" wire:click="retirer({{ $espace->id }})" aria-label="Retirer {{ $espace->nom }} des favoris"
                        class="absolute right-2.5 top-2.5 flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-brand shadow-carte">
                    <x-icone nom="coeur" taille="18" plein />
                </button>
            </div>
        @empty
            <x-vide titre="Aucun favori" sous-titre="Touchez le cœur sur la fiche d'un espace pour le retrouver ici." />
            <a href="/app" class="btn-contour">Découvrir les espaces</a>
        @endforelse
    </div>
</div>
