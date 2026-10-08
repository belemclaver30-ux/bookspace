@php
    $couleurs = ['confirmee' => '#16A34A', 'terminee' => '#BD3100', 'en_attente' => '#EB9F04', 'annulee' => '#DC2626'];
    $libelles = ['confirmee' => 'Confirmées', 'terminee' => 'Terminées', 'en_attente' => 'En attente', 'annulee' => 'Annulées'];
    $tuiles = [
        ["Chiffre d'affaires", fcfa($stats['revenus']['chiffre_affaires']), true],
        ['Net à recevoir', fcfa($stats['revenus']['net_reverse']), false],
        ['Réservations confirmées', $stats['reservations']['confirmees'], false],
        ['Heures réservées', heures_lisibles($stats['heures_reservees']) . ' h', false],
    ];
@endphp
<div>
    <h1 class="titre-page mb-4">Tableau de bord</h1>

    @unless ($gerant->estValide())
        <div class="mb-4 rounded-xl bg-[#FEF0D6] p-3.5 text-[13px] text-[#854D0E]">
            @if ($gerant->statut_validation === 'rejete')
                Votre compte gérant a été rejeté{{ $gerant->motif_rejet ? " : {$gerant->motif_rejet}" : '.' }}
            @else
                Votre compte gérant est en attente de validation par l'administrateur.
            @endif
        </div>
    @endunless

    <div class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.5px] text-slate-400">
        Du {{ \Carbon\Carbon::parse($stats['periode']['du'])->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($stats['periode']['au'])->format('d/m/Y') }}
    </div>

    <div class="mb-4 grid grid-cols-[repeat(auto-fill,minmax(min(100%,148px),1fr))] gap-3.5">
        @foreach ($tuiles as [$libelle, $valeur, $accent])
            <div @class(['rounded-[14px] p-4 shadow-carte', 'bg-brand-dark' => $accent, 'bg-white' => ! $accent])>
                <div @class(['mb-1 text-[11px]', 'text-white/55' => $accent, 'text-slate-400' => ! $accent])>{{ $libelle }}</div>
                <div @class(['text-lg font-extrabold', 'text-white' => $accent, 'text-slate-900' => ! $accent])>{{ $valeur }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,300px),1fr))] gap-4">
        <x-panneau titre="Évolution du chiffre d'affaires">
            <x-graph.aire :points="collect($stats['serie'])->map(fn ($s) => ['x' => \Carbon\Carbon::parse($s['date'])->format('j/n'), 'valeur' => $s['chiffre_affaires']])->all()" />
        </x-panneau>

        <x-panneau titre="Répartition des réservations">
            <x-graph.anneau :segments="collect($libelles)->map(fn ($l, $s) => ['libelle' => $l, 'valeur' => $stats['repartition_statuts'][$s], 'couleur' => $couleurs[$s]])->values()->all()" />
        </x-panneau>

        <x-panneau titre="Chiffre d'affaires par espace">
            <x-graph.barres :donnees="collect($stats['par_espace'])->map(fn ($e) => ['libelle' => $e['espace'] ?? '—', 'valeur' => $e['chiffre_affaires']])->all()" />
        </x-panneau>

        <x-panneau titre="Taux d'occupation">
            <x-graph.jauge :pct="$stats['taux_occupation']" libelle="des heures ouvrées réservées" />
            <div class="mt-1.5 text-center text-xs text-slate-500">
                Commission plateforme prélevée : <b>{{ fcfa($stats['revenus']['commissions_plateforme']) }}</b>
            </div>
        </x-panneau>
    </div>
</div>
