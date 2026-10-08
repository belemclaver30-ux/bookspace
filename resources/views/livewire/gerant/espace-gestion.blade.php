<div>
    <div class="flex items-center gap-3">
        <x-retour href="/gerant/espaces" />
        <h1 class="titre-page min-w-0 truncate">{{ $espace->nom }}</h1>
    </div>
    <div class="mb-5 mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] text-slate-500">
        {{ libelle_type($espace->type) }} · {{ $espace->adresse }}, {{ $espace->ville }}
        <x-badge-statut :statut="$espace->statut" />
    </div>
    @if (in_array($espace->statut, ['rejete', 'suspendu'], true) && $espace->motif_rejet)
        <div class="mb-4 rounded-xl bg-red-50 p-3.5 text-[13px] text-red-700">Motif : {{ $espace->motif_rejet }}</div>
    @endif

    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,320px),1fr))] items-start gap-4">

        {{-- ── Fiche ── --}}
        <section class="rounded-2xl bg-white p-4 shadow-carte-lg">
            <h2 class="titre-section mb-1">Fiche de l'espace</h2>
            <p class="mb-3 text-xs text-slate-400">Ce que voient les clients. Le type ({{ libelle_type($espace->type) }}) ne peut pas être changé.</p>

            <form wire:submit="enregistrerFiche">
                <x-erreur :message="collect(['nom', 'ville', 'tarif_horaire', 'capacite', 'type_pelouse', 'dimensions', 'equipements', 'description'])
                    ->map(fn ($champ) => $errors->first($champ))->filter()->first()" />

                <label class="mb-3 block">
                    <span class="etiquette">NOM</span>
                    <input type="text" wire:model="nom" class="champ" required maxlength="255">
                </label>
                <label class="mb-3 block">
                    <span class="etiquette">VILLE</span>
                    <input type="text" wire:model="ville" class="champ" required maxlength="255">
                </label>
                <label class="mb-3 block">
                    <span class="etiquette">TARIF HORAIRE (FCFA)</span>
                    <input type="number" wire:model="tarif_horaire" class="champ" required min="0" step="any" inputmode="numeric">
                </label>

                @if ($espace->type === 'salle_repetition')
                    <label class="mb-3 block">
                        <span class="etiquette">CAPACITÉ (personnes)</span>
                        <input type="number" wire:model="capacite" class="champ" min="1" step="1" inputmode="numeric">
                    </label>
                    <label class="mb-3 flex items-center gap-2 text-[14px] text-slate-700">
                        <input type="checkbox" wire:model="isolation_phonique" class="accent-brand"> Salle insonorisée
                    </label>
                @else
                    <label class="mb-3 block">
                        <span class="etiquette">TYPE DE PELOUSE</span>
                        <input type="text" wire:model="type_pelouse" class="champ" maxlength="100" placeholder="Synthétique, naturelle…">
                    </label>
                    <label class="mb-3 block">
                        <span class="etiquette">DIMENSIONS</span>
                        <input type="text" wire:model="dimensions" class="champ" maxlength="50" placeholder="Ex. 40 × 20 m">
                    </label>
                    <label class="mb-3 flex items-center gap-2 text-[14px] text-slate-700">
                        <input type="checkbox" wire:model="eclairage" class="accent-brand"> Éclairage pour jouer de nuit
                    </label>
                @endif

                <label class="mb-3 block">
                    <span class="etiquette">ÉQUIPEMENTS (séparés par des virgules)</span>
                    <input type="text" wire:model="equipements" class="champ" maxlength="1000" placeholder="WiFi, Climatisation, Parking">
                </label>
                <label class="mb-3 block">
                    <span class="etiquette">DESCRIPTION</span>
                    <textarea wire:model="description" rows="4" class="champ resize-y" maxlength="5000"></textarea>
                </label>

                <button type="submit" class="btn-primaire" wire:loading.attr="disabled" wire:target="enregistrerFiche">
                    <span wire:loading.remove wire:target="enregistrerFiche">Enregistrer la fiche</span>
                    <span wire:loading wire:target="enregistrerFiche">Enregistrement…</span>
                </button>
            </form>
            @if (session('fiche'))
                <div class="mt-2 text-[13px] font-semibold text-green-600" role="status">✓ {{ session('fiche') }}</div>
            @endif
        </section>

        {{-- ── Photos ── --}}
        <section class="rounded-2xl bg-white p-4 shadow-carte-lg">
            <h2 class="titre-section mb-1">Photos de l'espace</h2>
            <p class="mb-3 text-xs text-slate-400">
                Ajoutez de vraies photos : elles rassurent les clients et augmentent les réservations.
            </p>

            @error('nouvellesPhotos') <x-erreur :message="$message" /> @enderror
            @error('nouvellesPhotos.*') <x-erreur :message="$message" /> @enderror

            <div class="mb-3 grid grid-cols-[repeat(auto-fill,minmax(min(100%,110px),1fr))] gap-2.5">
                @foreach ($photos as $i => $photo)
                    <div wire:key="photo-{{ $photo->id }}" class="relative aspect-[4/3] overflow-hidden rounded-[10px] bg-slate-100">
                        <img src="{{ $photo->url }}" alt="Photo {{ $i + 1 }}" loading="lazy" class="h-full w-full object-cover">
                        @if ($i === 0)
                            <span class="absolute left-1.5 top-1.5 rounded-md bg-brand px-1.5 py-0.5 text-[9px] font-bold text-white">Principale</span>
                        @else
                            <button type="button" wire:click="photoPrincipale({{ $photo->id }})"
                                    class="absolute bottom-1 left-1 right-1 rounded-md bg-white/90 py-[3px] text-[10px] font-bold text-brand-dark">
                                Définir principale
                            </button>
                        @endif
                        <button type="button" wire:click="supprimerPhoto({{ $photo->id }})" wire:confirm="Supprimer cette photo ?" aria-label="Supprimer la photo {{ $i + 1 }}"
                                class="absolute right-1 top-1 h-[22px] w-[22px] rounded-full bg-black/55 text-[13px] leading-none text-white">×</button>
                    </div>
                @endforeach

                @if ($photos->count() < $photosMax)
                    <label class="flex aspect-[4/3] cursor-pointer flex-col items-center justify-center gap-1 rounded-[10px] border-2 border-dashed border-slate-200 bg-[#FFF8F3] text-xs font-semibold text-brand-dark focus-within:border-brand">
                        <span class="text-[22px] leading-none" aria-hidden="true">＋</span>
                        <span wire:loading.remove wire:target="nouvellesPhotos">Ajouter</span>
                        <span wire:loading wire:target="nouvellesPhotos">Envoi…</span>
                        <input type="file" wire:model="nouvellesPhotos" accept="image/*" multiple class="sr-only">
                    </label>
                @endif
            </div>
            <p class="text-[11px] text-slate-400">JPEG / PNG / WebP · 8 Mo max par photo · {{ $photosMax }} photos max.</p>
        </section>

        {{-- ── Localisation ── --}}
        <section class="rounded-2xl bg-white p-4 shadow-carte-lg"
                 x-data="carteLocalisation"
                 @point-deplace.window="deplacer($event.detail)">
            <h2 class="titre-section mb-1">Localisation</h2>
            <p class="mb-3 text-xs text-slate-400">Cherchez l'adresse, cliquez sur la carte ou déplacez le marqueur.</p>

            @error('localisation') <x-erreur :message="$message" /> @enderror
            @error('latitude') <x-erreur :message="$message" /> @enderror
            <div x-cloak x-show="erreur" x-text="erreur" role="alert" class="mb-3.5 rounded-xl bg-red-50 px-3.5 py-2.5 text-[13px] font-medium text-red-600"></div>

            <form wire:submit="chercherAdresse" class="mb-2 flex gap-2">
                <label class="flex-1">
                    <span class="sr-only">Rechercher une adresse</span>
                    <input type="search" wire:model="recherche" placeholder="Ex. Ouaga 2000, Rue 28.104" class="champ-compact w-full" minlength="3">
                </label>
                <button type="submit" wire:loading.attr="disabled" wire:target="chercherAdresse" class="rounded-[10px] bg-brand px-3.5 text-[13px] font-semibold text-white">
                    <span wire:loading.remove wire:target="chercherAdresse">Chercher</span>
                    <span wire:loading wire:target="chercherAdresse">…</span>
                </button>
            </form>

            @if ($resultats)
                <div class="mb-2.5 overflow-hidden rounded-[10px] border border-slate-200">
                    @foreach ($resultats as $i => $resultat)
                        <button type="button" wire:click="choisirResultat({{ $i }})" wire:key="resultat-{{ $i }}"
                                class="block w-full border-b border-slate-100 bg-white px-3 py-2.5 text-left text-[12.5px] text-slate-700 last:border-b-0 hover:bg-slate-50">
                            {{ $resultat['label'] }}
                        </button>
                    @endforeach
                </div>
            @endif

            <button type="button" @click="maPosition()" :disabled="occupe"
                    class="mb-2.5 w-full rounded-[10px] bg-brand-50 p-2.5 text-[13px] font-semibold text-brand-dark"
                    x-text="occupe ? 'Localisation…' : '📍 Utiliser ma position actuelle'">📍 Utiliser ma position actuelle</button>

            <div wire:ignore x-ref="carte" class="z-0 h-60 w-full overflow-hidden rounded-xl bg-[#E8EEF3]"></div>

            <label class="mt-2.5 block">
                <span class="mb-1 block text-[11px] font-semibold text-slate-600">ADRESSE AFFICHÉE</span>
                <input type="text" wire:model="adresse" class="champ-compact w-full" maxlength="255">
            </label>

            <div x-cloak x-show="point" class="mt-1.5 text-[11px] text-slate-400"
                 x-text="point ? point.lat.toFixed(5) + ', ' + point.lng.toFixed(5) : ''"></div>

            <button type="button" wire:click="enregistrerLocalisation" wire:loading.attr="disabled" wire:target="enregistrerLocalisation" class="btn-primaire mt-3">
                <span wire:loading.remove wire:target="enregistrerLocalisation">Enregistrer la localisation</span>
                <span wire:loading wire:target="enregistrerLocalisation">Enregistrement…</span>
            </button>
            @if (session('localisation'))
                <div class="mt-2 text-[13px] font-semibold text-green-600" role="status">✓ {{ session('localisation') }}</div>
            @endif
        </section>

        {{-- ── Horaires d'ouverture ── --}}
        <section class="rounded-2xl bg-white p-4 shadow-carte-lg">
            <h2 class="titre-section mb-3">Horaires d'ouverture</h2>

            @error('horaires') <x-erreur :message="$message" /> @enderror
            @error('horaires.*') <x-erreur :message="$message" /> @enderror

            <form wire:submit="enregistrerHoraires">
                @foreach ($jours as $j => $nom)
                    <div class="mb-2 flex items-center gap-2" wire:key="horaire-{{ $j }}" x-data="{ actif: $wire.entangle('horaires.{{ $j }}.actif') }">
                        <label class="flex w-[110px] shrink-0 items-center gap-1.5 text-[13px] text-slate-700">
                            <input type="checkbox" x-model="actif" class="accent-brand">{{ $nom }}
                        </label>
                        <input type="time" wire:model="horaires.{{ $j }}.o" :disabled="! actif" aria-label="Ouverture {{ $nom }}"
                               class="champ-compact min-w-0 flex-1 disabled:bg-slate-50 disabled:text-slate-400">
                        <span class="text-slate-400">–</span>
                        <input type="time" wire:model="horaires.{{ $j }}.f" :disabled="! actif" aria-label="Fermeture {{ $nom }}"
                               class="champ-compact min-w-0 flex-1 disabled:bg-slate-50 disabled:text-slate-400">
                    </div>
                @endforeach

                <button type="submit" class="btn-primaire mt-2.5" wire:loading.attr="disabled" wire:target="enregistrerHoraires">Enregistrer les horaires</button>
            </form>
            @if (session('horaires'))
                <div class="mt-2 text-[13px] font-semibold text-green-600" role="status">✓ {{ session('horaires') }}</div>
            @endif
        </section>

        {{-- ── Plages bloquées ── --}}
        <section class="rounded-2xl bg-white p-4 shadow-carte-lg">
            <h2 class="titre-section mb-3">Plages bloquées</h2>

            @error('blocageDebut') <x-erreur :message="$message" /> @enderror
            @error('blocageFin') <x-erreur :message="$message" /> @enderror

            @forelse ($blocages as $b)
                <div wire:key="blocage-{{ $b->id }}" class="mb-2 flex items-center justify-between gap-2 rounded-[10px] bg-slate-50 px-3 py-2.5">
                    <div class="text-xs text-slate-700">
                        {{ date_longue($b->date_debut) }} {{ $b->date_debut->format('H:i') }} →
                        {{ $b->date_debut->isSameDay($b->date_fin) ? '' : date_longue($b->date_fin) }} {{ $b->date_fin->format('H:i') }}
                        @if ($b->motif) · {{ $b->motif }} @endif
                    </div>
                    <button type="button" wire:click="debloquer({{ $b->id }})" class="shrink-0 text-xs font-semibold text-red-600">Débloquer</button>
                </div>
            @empty
                <div class="mb-3 text-[13px] text-slate-400">Aucune plage bloquée.</div>
            @endforelse

            <form wire:submit="bloquer" class="mt-2.5 flex flex-col gap-2">
                <label>
                    <span class="mb-1 block text-[11px] font-semibold text-slate-600">DÉBUT</span>
                    <input type="datetime-local" wire:model="blocageDebut" class="champ-compact w-full" required>
                </label>
                <label>
                    <span class="mb-1 block text-[11px] font-semibold text-slate-600">FIN</span>
                    <input type="datetime-local" wire:model="blocageFin" class="champ-compact w-full" required>
                </label>
                <label>
                    <span class="sr-only">Motif</span>
                    <input type="text" wire:model="blocageMotif" placeholder="Motif (optionnel)" class="champ-compact w-full" maxlength="255">
                </label>
                <button type="submit" class="btn-primaire" wire:loading.attr="disabled" wire:target="bloquer">Bloquer cette plage</button>
            </form>
        </section>
    </div>
</div>
