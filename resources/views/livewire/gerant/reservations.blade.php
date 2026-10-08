@php
    $moisNoms = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    $couleurs = ['confirmee' => '#16A34A', 'terminee' => '#BD3100', 'en_attente' => '#EB9F04', 'annulee' => '#DC2626'];
    $aujourdHui = now()->toDateString();
    $pluriel = fn (int $n) => $n . ' réservation' . ($n > 1 ? 's' : '');
@endphp
<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="titre-page">Réservations reçues</h1>
        @unless ($ajout)
            <button type="button" wire:click="basculerAjout" class="rounded-[10px] bg-brand px-4 py-[9px] text-[13px] font-bold text-white">
                + Ajouter une réservation
            </button>
        @endunless
    </div>

    {{-- ── Réservation manuelle (client au comptoir / téléphone) ── --}}
    @if ($ajout)
        <form wire:submit="creerManuelle" class="carte mb-4">
            <div class="titre-section mb-2.5 text-[13px]">Caler une réservation manuellement</div>
            <p class="mb-3 text-xs text-slate-500">
                Pour un client qui a réservé par téléphone ou au comptoir. La réservation est confirmée immédiatement.
            </p>

            <x-erreur />

            <div class="mb-3 grid grid-cols-[repeat(auto-fit,minmax(160px,1fr))] gap-2.5">
                <label>
                    <span class="sr-only">Espace</span>
                    <select wire:model="espaceId" class="champ-compact w-full" required>
                        <option value="">Choisir un espace…</option>
                        @foreach ($espacesValides as $e)
                            <option value="{{ $e->id }}">{{ $e->nom }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="sr-only">Créneau</span>
                    <input type="datetime-local" wire:model="date" class="champ-compact w-full" required>
                </label>
                <label>
                    <span class="sr-only">Durée</span>
                    <select wire:model="duree" class="champ-compact w-full">
                        @foreach (range(1, 6) as $h)
                            <option value="{{ $h }}">{{ $h }} h</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="sr-only">Nom du client</span>
                    <input type="text" wire:model="clientNom" placeholder="Nom du client" class="champ-compact w-full" required maxlength="255">
                </label>
                <label>
                    <span class="sr-only">Téléphone du client</span>
                    <input type="tel" wire:model="clientTelephone" placeholder="Téléphone du client" class="champ-compact w-full" required maxlength="30">
                </label>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-mini flex-1 bg-brand py-2.5 text-white" wire:loading.attr="disabled" wire:target="creerManuelle">Confirmer la réservation</button>
                <button type="button" wire:click="basculerAjout" class="btn-mini bg-slate-100 py-2.5 text-slate-600">Annuler</button>
            </div>
        </form>
    @endif

    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,320px),1fr))] items-start gap-4">

        {{-- ── Calendrier du mois ── --}}
        <div class="carte">
            <div class="mb-3.5 flex items-center justify-between">
                <button type="button" wire:click="changerMois(-1)" aria-label="Mois précédent"
                        class="h-[30px] w-[30px] rounded-lg border-[1.5px] border-slate-200 bg-white text-base leading-none text-slate-600">‹</button>
                <div class="font-display text-[15px] font-bold text-brand-dark">
                    {{ $moisNoms[$debut->month - 1] }} {{ $debut->year }}
                    <span class="ml-2 font-sans text-xs font-medium text-slate-400">{{ $pluriel($totalMois) }}</span>
                </div>
                <button type="button" wire:click="changerMois(1)" aria-label="Mois suivant"
                        class="h-[30px] w-[30px] rounded-lg border-[1.5px] border-slate-200 bg-white text-base leading-none text-slate-600">›</button>
            </div>

            <div class="grid grid-cols-7 gap-1">
                @foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $nom)
                    <div class="py-0.5 text-center text-[10px] font-bold text-slate-400">{{ $nom }}</div>
                @endforeach

                @foreach ($cellules as $d)
                    @php
                        $cle = $d->toDateString();
                        $resas = $parJour[$cle] ?? collect();
                        $dansLeMois = $d->month === $debut->month;
                        $selectionne = $cle === $jour;
                        $estAujourdHui = $cle === $aujourdHui;
                    @endphp
                    <button type="button" wire:key="jour-{{ $cle }}"
                            @if ($resas->isNotEmpty()) wire:click="choisirJour('{{ $cle }}')" aria-pressed="{{ $selectionne ? 'true' : 'false' }}" @else disabled @endif
                            aria-label="{{ date_longue($d) }} — {{ $pluriel($resas->count()) }}"
                            @class([
                                'flex aspect-square min-h-10 flex-col items-center justify-start rounded-[10px] p-[3px]',
                                'bg-brand' => $selectionne,
                                'bg-brand-50' => ! $selectionne && $resas->isNotEmpty(),
                                'opacity-35' => ! $dansLeMois,
                                'outline outline-[1.5px] -outline-offset-[1.5px] outline-brand' => $estAujourdHui && ! $selectionne,
                            ])>
                        <span @class([
                            'text-xs',
                            'font-extrabold' => $selectionne || $estAujourdHui,
                            'font-medium' => ! $selectionne && ! $estAujourdHui,
                            'text-white' => $selectionne,
                            'text-slate-700' => ! $selectionne && $dansLeMois,
                            'text-slate-400' => ! $selectionne && ! $dansLeMois,
                        ])>{{ $d->day }}</span>
                        @if ($resas->isNotEmpty())
                            <span class="mt-[3px] flex flex-wrap justify-center gap-0.5">
                                @foreach ($resas->take(3) as $r)
                                    <span class="h-[5px] w-[5px] rounded-full" style="background: {{ $selectionne ? '#fff' : ($couleurs[$r->statut] ?? '#94A3B8') }}"></span>
                                @endforeach
                                @if ($resas->count() > 3)
                                    <span @class(['text-[8px] font-bold leading-[5px]', 'text-white' => $selectionne, 'text-brand-dark' => ! $selectionne])>+{{ $resas->count() - 3 }}</span>
                                @endif
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="mt-3 flex flex-wrap gap-3 text-[10.5px] text-slate-500">
                @foreach (['confirmee' => 'Confirmée', 'terminee' => 'Terminée', 'en_attente' => 'En attente', 'annulee' => 'Annulée'] as $s => $libelle)
                    <span class="flex items-center gap-1">
                        <span class="h-[7px] w-[7px] rounded-full" style="background: {{ $couleurs[$s] }}"></span>{{ $libelle }}
                    </span>
                @endforeach
            </div>
        </div>

        {{-- ── Liste ── --}}
        <div>
            <div class="mb-3 flex flex-wrap gap-2">
                @foreach ($filtres as $id => $libelle)
                    <button type="button" wire:click="filtrer('{{ $id }}')" @class(['puce', 'puce-active' => $statut === $id])
                            aria-pressed="{{ $statut === $id ? 'true' : 'false' }}">{{ $libelle }}</button>
                @endforeach
            </div>

            <div class="mb-2.5 text-xs font-semibold text-slate-400">
                @if ($jour)
                    {{ date_longue(\Carbon\CarbonImmutable::parse($jour)) }} — {{ $pluriel($liste->count()) }}
                    <button type="button" wire:click="choisirJour(null)" class="ml-2 text-xs font-bold text-brand">voir tout le mois</button>
                @else
                    {{ $pluriel($liste->count()) }} ce mois-ci
                @endif
            </div>

            @error('annulation') <x-erreur :message="$message" /> @enderror

            @if ($liste->isEmpty())
                <x-vide titre="Aucune réservation" :sous-titre="$jour ? 'Rien ce jour-là.' : 'Rien ce mois-ci.'" />
            @endif

            <div class="flex flex-col gap-3">
                @foreach ($liste as $r)
                    @php $nomClient = $r->client?->name ?? $r->client_nom; $telClient = $r->client?->telephone ?? $r->client_telephone; @endphp
                    <div wire:key="resa-{{ $r->id }}" class="carte">
                        <div class="mb-2 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate text-[15px] font-bold text-brand-dark">{{ $r->espace?->nom }}</div>
                                <div class="text-xs text-slate-400">
                                    {{ $r->reference }} · {{ $nomClient }}
                                    @if ($telClient) · <a href="tel:{{ $telClient }}" class="underline">{{ $telClient }}</a> @endif
                                    @if ($r->origine === 'gerant')
                                        <span class="ml-1 rounded-full bg-brand-50 px-[7px] py-0.5 text-[10px] font-bold text-brand-dark">Ajoutée manuellement</span>
                                    @endif
                                </div>
                            </div>
                            <x-badge-statut :statut="$r->statut" />
                        </div>

                        <div class="mb-1.5 flex items-center gap-1.5 text-xs text-slate-500">
                            <x-icone nom="calendrier" taille="16" class="text-slate-400" />
                            {{ date_longue($r->date_debut) }} · {{ plage_horaire($r->date_debut, $r->date_fin) }}
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">Net {{ fcfa($r->montant_reverse_gerant) }}</span>
                            <span class="text-[13px] font-bold text-brand">{{ fcfa($r->prix_total) }}</span>
                        </div>

                        @if ($r->motif_annulation)
                            <div class="mt-2 text-xs text-red-700">Annulée : {{ $r->motif_annulation }}</div>
                        @endif

                        @if ($r->client_id)
                            <a href="/messages/reservation/{{ $r->id }}" class="mt-3 inline-flex items-center gap-1.5 rounded-[9px] bg-slate-100 px-3.5 py-2 text-[12.5px] font-bold text-slate-700">
                                <x-icone nom="message" taille="15" /> Message au client
                            </a>
                        @endif

                        @if ($r->estAnnulable())
                            @if ($reportId === $r->id)
                                <form wire:submit="reporter" class="mt-3 border-t border-slate-100 pt-3">
                                    <div class="mb-2 text-xs font-bold text-brand-dark">Nouveau créneau</div>
                                    @error('reportDate') <x-erreur :message="$message" /> @enderror
                                    @error('date_debut') <x-erreur :message="$message" /> @enderror
                                    <div class="mb-2.5 flex flex-wrap gap-2">
                                        <label class="flex-[1_1_180px]">
                                            <span class="sr-only">Nouveau créneau</span>
                                            <input type="datetime-local" wire:model="reportDate" class="champ-compact w-full" required>
                                        </label>
                                        <label>
                                            <span class="sr-only">Durée</span>
                                            <select wire:model="reportDuree" class="champ-compact">
                                                @foreach (range(1, 6) as $h)
                                                    <option value="{{ $h }}">{{ $h }} h</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="submit" class="btn-mini flex-1 bg-brand text-white" wire:loading.attr="disabled" wire:target="reporter">Confirmer le report</button>
                                        <button type="button" wire:click="ouvrirReport(null)" class="btn-mini bg-slate-100 text-slate-600">Annuler</button>
                                    </div>
                                </form>
                            @else
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button type="button" wire:click="ouvrirReport({{ $r->id }})" class="btn-mini bg-brand-50 text-brand-dark">Reporter</button>
                                    <button type="button" class="btn-mini bg-red-100 text-red-600"
                                            x-data @click="const motif = prompt('Motif de l\'annulation (communiqué au client) :'); if (motif) $wire.annuler({{ $r->id }}, motif)">
                                        Annuler
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
