@php
    $couleurs = ['confirmee' => '#16A34A', 'terminee' => '#BD3100', 'en_attente' => '#EB9F04', 'annulee' => '#DC2626'];
    $libelles = ['confirmee' => 'Confirmées', 'terminee' => 'Terminées', 'en_attente' => 'En attente', 'annulee' => 'Annulées'];
    $moisCourts = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    // [libellé, valeur, alerte (rouge si > 0), accent, lien]
    $tuiles = [
        ['Clients', $tb['utilisateurs']['clients'], false, false, null],
        ['Gérants', $tb['utilisateurs']['gerants'], false, false, null],
        ['Gérants à valider', $tb['gerants_en_attente'], $tb['gerants_en_attente'] > 0, false, '/admin/validations'],
        ['Espaces publiés', $tb['espaces']['valides'], false, false, null],
        ['Espaces à valider', $tb['espaces']['en_attente'], $tb['espaces']['en_attente'] > 0, false, '/admin/validations?onglet=espaces'],
        ['Réservations confirmées', $tb['reservations']['confirmees'], false, false, null],
        ['Commissions perçues', fcfa($tb['commissions_percues']), false, true, null],
        ['Litiges ouverts', $tb['litiges_ouverts'], $tb['litiges_ouverts'] > 0, false, '/admin/litiges'],
    ];
@endphp
<div>
    <h1 class="titre-page mb-4">Tableau de bord</h1>

    <div class="mb-4 grid grid-cols-[repeat(auto-fill,minmax(min(100%,148px),1fr))] gap-3.5">
        @foreach ($tuiles as [$libelle, $valeur, $alerte, $accent, $lien])
            <{{ $lien ? 'a' : 'div' }} @if ($lien) href="{{ $lien }}" @endif
                @class(['block rounded-[14px] p-5 shadow-carte', 'bg-brand-dark' => $accent, 'bg-white' => ! $accent])>
                <div @class(['mb-1.5 text-xs', 'text-white/60' => $accent, 'text-slate-500' => ! $accent])>{{ $libelle }}</div>
                <div @class(['text-[22px] font-extrabold', 'text-white' => $accent, 'text-red-600' => $alerte, 'text-slate-900' => ! $accent && ! $alerte])>{{ $valeur }}</div>
            </{{ $lien ? 'a' : 'div' }}>
        @endforeach
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,300px),1fr))] gap-4">
        <x-panneau titre="Commissions perçues (6 mois)">
            <x-graph.aire :points="collect($tb['commissions_par_mois'])->map(fn ($m) => ['x' => $moisCourts[(int) substr($m['mois'], 5, 2) - 1], 'valeur' => $m['commissions']])->all()" />
        </x-panneau>

        <x-panneau titre="Réservations par statut">
            <x-graph.anneau :segments="collect($libelles)->map(fn ($l, $s) => ['libelle' => $l, 'valeur' => (int) ($tb['reservations_par_statut'][$s] ?? 0), 'couleur' => $couleurs[$s]])->values()->all()" />
        </x-panneau>

        <x-panneau titre="Espaces par type">
            <x-graph.anneau :segments="[
                ['libelle' => libelle_type('salle_repetition'), 'valeur' => $tb['espaces_par_type']['salle_repetition'], 'couleur' => '#FC4E00'],
                ['libelle' => libelle_type('terrain_foot'), 'valeur' => $tb['espaces_par_type']['terrain_foot'], 'couleur' => '#F3BB00'],
            ]" />
        </x-panneau>

        <x-panneau titre="Top espaces (chiffre d'affaires)">
            <x-graph.barres :donnees="collect($tb['top_espaces'])->map(fn ($e) => ['libelle' => $e['espace'] ?? '—', 'valeur' => $e['chiffre_affaires']])->all()" />
        </x-panneau>
    </div>
</div>
