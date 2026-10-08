@php
    $galerie = $espace->photos->pluck('url')->all() ?: [\App\Models\Espace::PHOTO_PAR_DEFAUT];
    $peutReserver = auth()->user()->estClient();
    $salle = $espace->type === 'salle_repetition';
    $faits = $salle
        ? ['Capacité' => $espace->capacite ? "{$espace->capacite} pers." : '—', 'Insonorisé' => $espace->isolation_phonique ? 'Oui' : 'Non']
        : ['Pelouse' => $espace->type_pelouse ?? '—', 'Éclairage' => $espace->eclairage ? 'Oui' : 'Non', 'Dimensions' => $espace->dimensions ?? '—'];
@endphp
<div class="flex flex-1 flex-col" x-data="{ onglet: 'info', photo: 0, galerie: @js($galerie) }">
    <div class="relative h-60 shrink-0">
        <img :src="galerie[photo]" src="{{ $galerie[0] }}" alt="{{ $espace->nom }}" class="h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-b from-[rgba(120,40,15,0.35)] to-transparent to-45%"></div>
        <div class="absolute left-4 top-4"><x-retour /></div>
        @if ($peutReserver)
            <button type="button" wire:click="basculerFavori" aria-pressed="{{ $favori ? 'true' : 'false' }}"
                    aria-label="{{ $favori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}"
                    class="absolute right-4 top-4 flex h-[38px] w-[38px] items-center justify-center rounded-[10px] bg-white text-brand shadow-carte">
                <x-icone nom="coeur" taille="20" :plein="$favori" />
            </button>
        @endif
        @if (count($galerie) > 1)
            <div class="absolute bottom-2.5 left-3 right-3 flex gap-1.5 overflow-x-auto pb-0.5">
                @foreach ($galerie as $i => $url)
                    <button type="button" @click="photo = {{ $i }}" aria-label="Photo {{ $i + 1 }}"
                            class="h-10 w-[52px] shrink-0 overflow-hidden rounded-lg border-2 p-0"
                            :class="photo === {{ $i }} ? 'border-white' : 'border-white/50'">
                        <img src="{{ $url }}" alt="" class="h-full w-full object-cover">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex-1 px-5 pt-5">
        <div class="mb-2 flex items-start justify-between gap-3">
            <div>
                <h1 class="font-display text-xl font-bold text-brand-dark">{{ $espace->nom }}</h1>
                <div class="mt-[3px] flex items-center gap-1 text-[13px] text-slate-500">
                    <x-icone nom="lieu" taille="13" />{{ $espace->adresse }} · {{ $espace->ville }}
                </div>
            </div>
            <div class="shrink-0 text-right">
                <div class="text-lg font-bold text-brand">{{ nombre($espace->tarif_horaire) }}</div>
                <div class="text-[11px] text-slate-400">FCFA / heure</div>
            </div>
        </div>

        <div class="mb-[18px] flex items-center gap-1.5 rounded-xl bg-slate-50 px-3.5 py-2.5">
            <x-icone nom="etoile" taille="13" plein class="text-gold" />
            <span class="text-sm font-bold text-gray-700">{{ number_format((float) $espace->note_moyenne, 1, ',', '') }}</span>
            <span class="text-xs text-slate-400">
                · {{ $espace->nb_avis }} avis · {{ libelle_type($espace->type) }}
                @if ($espace->gerant?->estValide()) · Gérant vérifié ✓ @endif
            </span>
        </div>

        <div class="mb-[18px] flex border-b-2 border-slate-100" role="tablist">
            @foreach (['info' => 'Informations', 'equip' => 'Équipements', 'avis' => 'Avis (' . $espace->evaluations->count() . ')'] as $cle => $libelle)
                <button type="button" role="tab" @click="onglet = '{{ $cle }}'" :aria-selected="onglet === '{{ $cle }}'"
                        class="-mb-0.5 flex-1 border-b-2 py-2.5 text-[13px] font-semibold"
                        :class="onglet === '{{ $cle }}' ? 'border-brand text-brand' : 'border-transparent text-slate-400'">
                    {{ $libelle }}
                </button>
            @endforeach
        </div>

        <div x-show="onglet === 'info'" class="pb-5 text-[13px] leading-[1.7] text-slate-500">
            {{ $espace->description ?: 'Aucune description fournie.' }}

            <div class="mt-4 grid grid-cols-2 gap-2.5">
                @foreach ($faits as $libelle => $valeur)
                    <div class="rounded-xl bg-slate-50 p-3">
                        <div class="mb-0.5 text-[11px] text-slate-400">{{ $libelle }}</div>
                        <div class="text-[13px] font-semibold text-slate-700">{{ $valeur }}</div>
                    </div>
                @endforeach
            </div>

            @if ($espace->latitude !== null && $espace->longitude !== null)
                <div class="mt-[18px]" x-data="carteItineraire({ lat: {{ $espace->latitude }}, lng: {{ $espace->longitude }} })">
                    <div class="titre-section mb-2 text-[13px]">Localisation</div>

                    <div wire:ignore x-ref="carte" class="z-0 h-[200px] w-full overflow-hidden rounded-xl bg-[#E8EEF3]"></div>

                    <div x-cloak x-show="route" class="mt-2.5 flex items-center justify-center gap-4 rounded-xl bg-brand-50 px-3.5 py-2.5 text-sm font-bold text-brand-dark">
                        <span>🚗 <span x-text="route?.distance"></span></span>
                        <span class="text-[#CBB8AC]">·</span>
                        <span>⏱ <span x-text="route?.duree"></span></span>
                    </div>

                    <div x-cloak x-show="erreur" x-text="erreur" role="alert"
                         class="mt-2.5 rounded-xl bg-red-50 px-3.5 py-2.5 text-[13px] font-medium text-red-600"></div>

                    <div class="mt-2.5 flex gap-2.5">
                        <button type="button" @click="calculer()" :disabled="occupe"
                                class="flex-1 rounded-xl bg-brand p-3 text-[13.5px] font-bold text-white disabled:bg-slate-100 disabled:text-slate-400"
                                x-text="occupe ? 'Calcul…' : (route ? 'Recalculer' : 'Calculer l\'itinéraire')">Calculer l'itinéraire</button>
                        <a :href="lienNavigation" target="_blank" rel="noopener noreferrer"
                           class="flex-1 rounded-xl border-[1.5px] border-brand bg-white p-3 text-center text-[13.5px] font-bold text-brand">
                            Ouvrir dans Maps
                        </a>
                    </div>
                    <div class="mt-1.5 text-center text-[11px] text-slate-400">Itinéraire routier vers « {{ $espace->nom }} »</div>
                </div>
            @else
                <div class="mt-[18px] text-xs text-slate-400">
                    <span class="font-bold text-brand-dark">Localisation</span> non renseignée par le gérant.
                </div>
            @endif
        </div>

        <div x-cloak x-show="onglet === 'equip'" class="grid grid-cols-2 gap-2.5 pb-5">
            @forelse ($espace->equipements ?? [] as $equipement)
                <div class="flex items-center gap-2 rounded-xl bg-slate-50 p-3">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-dark">
                        <x-icone nom="equipement" taille="14" />
                    </div>
                    <span class="text-xs font-medium text-slate-700">{{ $equipement }}</span>
                </div>
            @empty
                <div class="text-[13px] text-slate-400">Non renseigné.</div>
            @endforelse
        </div>

        <div x-cloak x-show="onglet === 'avis'" class="flex flex-col gap-3 pb-5">
            @forelse ($espace->evaluations as $avis)
                <div class="rounded-xl bg-slate-50 p-3.5">
                    <div class="mb-1 flex justify-between">
                        <span class="text-[13px] font-semibold text-brand-dark">{{ $avis->client?->name ?? 'Client' }}</span>
                        <span class="flex gap-0.5 text-gold" aria-label="{{ $avis->note }} sur 5">
                            @for ($n = 1; $n <= 5; $n++)
                                <x-icone nom="etoile" taille="13" :plein="$n <= $avis->note" />
                            @endfor
                        </span>
                    </div>
                    @if ($avis->commentaire)
                        <div class="text-xs leading-relaxed text-slate-500">{{ $avis->commentaire }}</div>
                    @endif
                </div>
            @empty
                <div class="text-[13px] text-slate-400">Pas encore d'avis.</div>
            @endforelse
        </div>
    </div>

    <div class="sticky bottom-0 mt-auto border-t border-slate-100 bg-white px-5 pb-[calc(14px+env(safe-area-inset-bottom))] pt-3.5">
        @if ($peutReserver)
            <a href="/espaces/{{ $espace->id }}/reserver" class="btn-primaire">Réserver maintenant</a>
        @else
            <button type="button" class="btn-primaire" disabled>Réservation réservée aux comptes clients</button>
        @endif
    </div>
</div>
