@php
    $badges = [
        'en_attente' => ['bg-[#FEF0D6] text-attente', 'À traiter'],
        'acceptee' => ['bg-green-100 text-green-600', 'Acceptée'],
        'refusee' => ['bg-red-100 text-red-600', 'Refusée'],
    ];
@endphp
<div>
    <h1 class="titre-page">Demandes des clients</h1>
    <p class="mb-4 mt-1 text-[13px] text-slate-500">
        Annulations et reports demandés sur des réservations confirmées. À vous de valider.
    </p>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ($filtres as $id => $libelle)
            <button type="button" wire:click="filtrer('{{ $id }}')" @class(['puce', 'puce-active' => $statut === $id])
                    aria-pressed="{{ $statut === $id ? 'true' : 'false' }}">{{ $libelle }}</button>
        @endforeach
    </div>

    <x-erreur />

    @if ($demandes->isEmpty())
        <x-vide titre="Aucune demande" :sous-titre="$statut === 'en_attente' ? 'Rien à traiter pour le moment.' : null" />
    @endif

    <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,340px),1fr))] gap-4">
        @foreach ($demandes as $d)
            @php
                $r = $d->reservation;
                [$classes, $libelle] = $badges[$d->statut] ?? ['bg-slate-100 text-slate-500', 'Retirée'];
                $confirmation = $d->type === 'annulation'
                    ? 'Accepter : la réservation sera annulée et le client prévenu.'
                    : 'Accepter : la réservation sera déplacée au créneau proposé.';
            @endphp
            <div wire:key="demande-{{ $d->id }}" class="carte">
                <div class="mb-2 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-[15px] font-bold text-brand-dark">{{ $d->type === 'annulation' ? 'Annulation' : 'Report' }}</div>
                        <div class="text-xs text-slate-400">{{ $r?->espace?->nom }} · {{ $r?->reference }} · {{ $d->demandeur?->name }}</div>
                    </div>
                    <span class="shrink-0 rounded-full px-[9px] py-[3px] text-[11px] font-bold {{ $classes }}">{{ $libelle }}</span>
                </div>

                @if ($r)
                    <div class="mb-1.5 text-xs text-slate-500">
                        Créneau actuel : {{ date_longue($r->date_debut) }} · {{ plage_horaire($r->date_debut, $r->date_fin) }} · {{ fcfa($r->prix_total) }}
                    </div>
                @endif

                @if ($d->type === 'report' && $d->nouvelle_date_debut)
                    <div class="mb-1.5 text-[13px] font-semibold text-brand">
                        → Nouveau créneau souhaité : {{ date_longue($d->nouvelle_date_debut) }} à {{ $d->nouvelle_date_debut->format('H:i') }}
                        @if ($d->nouvelle_duree_heures) ({{ heures_lisibles($d->nouvelle_duree_heures) }} h) @endif
                    </div>
                @endif

                @if ($d->motif)
                    <div class="mb-2 rounded-lg bg-slate-50 px-2.5 py-2 text-xs text-slate-600">« {{ $d->motif }} »</div>
                @endif

                @if ($d->reponse_gerant)
                    <div class="mb-2 text-xs text-red-700">Votre réponse : « {{ $d->reponse_gerant }} »</div>
                @endif

                @if ($d->statut === 'en_attente')
                    <div class="mt-2.5 flex gap-2">
                        <button type="button" wire:click="accepter({{ $d->id }})" wire:confirm="{{ $confirmation }}" wire:loading.attr="disabled"
                                class="flex-1 rounded-[9px] bg-green-600 py-[9px] text-[13px] font-bold text-white">Accepter</button>
                        <button type="button" wire:loading.attr="disabled" class="flex-1 rounded-[9px] bg-red-100 py-[9px] text-[13px] font-bold text-red-600"
                                x-data @click="const reponse = prompt('Message pour le client (facultatif) :'); if (reponse !== null) $wire.refuser({{ $d->id }}, reponse)">
                            Refuser
                        </button>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
