@php
    $moisNoms = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    $tuiles = [
        ["Chiffre d'affaires", fcfa($total['chiffre_affaires']), true],
        ['Versé par BookSpace', fcfa($total['verse']), false],
        ['Encaissé sur place', fcfa($total['sur_place']), false],
        ['Commission plateforme', fcfa($total['commission']), false],
    ];
@endphp
<div>
    <x-entete-profil titre="Mes revenus" sous-titre="Relevé des 12 derniers mois (réservations confirmées et terminées)" />

    <div class="mb-4 grid grid-cols-[repeat(auto-fill,minmax(min(100%,180px),1fr))] gap-3.5">
        @foreach ($tuiles as [$libelle, $valeur, $accent])
            <div @class(['rounded-[14px] p-4 shadow-carte', 'bg-brand-dark' => $accent, 'bg-white' => ! $accent])>
                <div @class(['mb-1 text-[11px]', 'text-white/55' => $accent, 'text-slate-400' => ! $accent])>{{ $libelle }}</div>
                <div @class(['text-lg font-extrabold', 'text-white' => $accent, 'text-slate-900' => ! $accent])>{{ $valeur }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3 rounded-[14px] bg-white px-4 py-3.5 shadow-carte">
        <x-icone nom="revenus" class="text-brand" />
        <div class="min-w-0 flex-1 text-[13px] text-slate-600">
            @if ($operateur)
                Versements sur <b>{{ $operateur }}</b> · {{ $gerant->momo_numero }} ({{ $gerant->momo_titulaire }})
            @else
                <b class="text-attente">Aucun compte de versement renseigné.</b> Indiquez votre numéro Mobile Money pour recevoir vos revenus.
            @endif
        </div>
        <a href="/profil/compte" class="text-[13px] font-semibold text-brand">{{ $operateur ? 'Modifier' : 'Renseigner' }}</a>
    </div>

    <div class="overflow-hidden rounded-[14px] bg-white shadow-carte">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[620px] text-left text-[13px]">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-[0.5px] text-slate-400">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 font-bold">Mois</th>
                        <th scope="col" class="px-4 py-2.5 text-right font-bold">Résa.</th>
                        <th scope="col" class="px-4 py-2.5 text-right font-bold">Chiffre d'affaires</th>
                        <th scope="col" class="px-4 py-2.5 text-right font-bold">Acomptes en ligne</th>
                        <th scope="col" class="px-4 py-2.5 text-right font-bold">Commission</th>
                        <th scope="col" class="px-4 py-2.5 text-right font-bold">Versé par BookSpace</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($mois as $m)
                        <tr @class(['text-slate-400' => $m['reservations'] === 0])>
                            <th scope="row" class="px-4 py-2.5 font-semibold text-slate-700">{{ $moisNoms[$m['mois']->month - 1] }} {{ $m['mois']->year }}</th>
                            <td class="px-4 py-2.5 text-right">{{ $m['reservations'] }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-slate-800">{{ fcfa($m['chiffre_affaires']) }}</td>
                            <td class="px-4 py-2.5 text-right">{{ fcfa($m['acomptes']) }}</td>
                            <td class="px-4 py-2.5 text-right">− {{ fcfa($m['commission']) }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-brand">{{ fcfa($m['verse']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <p class="mt-3 text-xs leading-normal text-slate-500">
        « Versé par BookSpace » = acomptes payés en ligne moins la commission. Le solde de chaque réservation, et la totalité
        des réservations ajoutées manuellement, sont encaissés directement sur place.
    </p>
</div>
