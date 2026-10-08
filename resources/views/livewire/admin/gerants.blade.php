<div>
    <h1 class="titre-page mb-1">Gérants &amp; reversements</h1>
    <p class="mb-4 text-[13px] text-slate-500">
        BookSpace encaisse les acomptes en ligne et reverse au gérant sa part (acompte − commission) une fois le créneau terminé.
        Faites le virement Mobile Money, puis enregistrez-le ici avec la référence de la transaction : le gérant est prévenu par SMS.
    </p>

    @if (session('succes'))
        <div class="mb-4 rounded-xl bg-green-100 p-3.5 text-[13px] font-semibold text-green-700" role="status">{{ session('succes') }}</div>
    @endif

    <div class="mb-4 grid grid-cols-[repeat(auto-fill,minmax(min(100%,200px),1fr))] gap-3.5">
        <div class="rounded-[14px] bg-brand-dark p-4 shadow-carte">
            <div class="mb-1 text-[11px] text-white/55">À reverser maintenant</div>
            <div class="text-lg font-extrabold text-white">{{ fcfa($totalDu) }}</div>
        </div>
        <div class="rounded-[14px] bg-white p-4 shadow-carte">
            <div class="mb-1 text-[11px] text-slate-400">À venir (créneaux confirmés)</div>
            <div class="text-lg font-extrabold text-slate-900">{{ fcfa($totalAVenir) }}</div>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <label class="min-w-56 flex-1">
            <span class="sr-only">Rechercher un gérant</span>
            <input type="search" wire:model.live.debounce.400ms="recherche" placeholder="Nom, téléphone, numéro Mobile Money…"
                   class="champ-compact w-full text-base">
        </label>
        <label class="flex items-center gap-2 text-[13px] font-semibold text-slate-600">
            <input type="checkbox" wire:model.live="aPayer" class="accent-brand"> Seulement les gérants à payer
        </label>
    </div>

    @forelse ($gerants as $g)
        @php $s = $soldes[$g->id] ?? $vide; @endphp
        <x-panneau wire:key="gerant-{{ $g->id }}" class="mb-4">
            <div class="flex flex-wrap justify-between gap-3">
                <div class="min-w-60 flex-1">
                    <div class="text-[15px] font-bold text-brand-dark">{{ $g->raison_sociale }}</div>
                    <div class="text-[13px] text-slate-500">
                        {{ $g->user?->name }} · {{ $g->user?->telephone }}
                        @if ($g->ville) · {{ $g->ville }} @endif
                        · {{ $g->espaces_count }} espace{{ $g->espaces_count > 1 ? 's' : '' }}
                    </div>
                    <div class="mt-1 text-[13px]">
                        @if ($g->peutRecevoirVersement())
                            <span class="text-slate-600">Versements : <b>{{ \App\Models\Gerant::OPERATEURS[$g->momo_operateur] }}</b> · {{ $g->momo_numero }}
                                @if ($g->momo_titulaire) ({{ $g->momo_titulaire }}) @endif</span>
                        @else
                            <span class="font-semibold text-attente">Aucun compte Mobile Money renseigné</span>
                        @endif
                    </div>
                </div>

                <dl class="grid grid-cols-3 gap-4 text-right text-[12px]">
                    <div>
                        <dt class="text-slate-400">À reverser</dt>
                        <dd @class(['text-[15px] font-extrabold', 'text-brand' => $s['du'] > 0, 'text-slate-400' => $s['du'] <= 0])>{{ fcfa($s['du']) }}</dd>
                        @if ($s['nb_du']) <dd class="text-slate-400">{{ $s['nb_du'] }} résa.</dd> @endif
                    </div>
                    <div>
                        <dt class="text-slate-400">À venir</dt>
                        <dd class="text-[15px] font-bold text-slate-700">{{ fcfa($s['a_venir']) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Déjà reversé</dt>
                        <dd class="text-[15px] font-bold text-slate-700">{{ fcfa($s['reverse']) }}</dd>
                    </div>
                </dl>
            </div>

            @if ($s['du'] > 0 && $g->peutRecevoirVersement())
                @if ($versement === $g->id)
                    <form wire:submit="enregistrerVersement" class="mt-3.5 rounded-xl bg-slate-50 p-3.5">
                        <div class="mb-2.5 text-[13px] text-slate-700">
                            Envoyez <b>{{ fcfa($s['du']) }}</b> au {{ $g->momo_numero }} ({{ \App\Models\Gerant::OPERATEURS[$g->momo_operateur] }}), puis :
                        </div>
                        @error('reference') <x-erreur :message="$message" /> @enderror
                        @error('note') <x-erreur :message="$message" /> @enderror
                        <label class="mb-2.5 block">
                            <span class="etiquette">RÉFÉRENCE DE LA TRANSACTION</span>
                            <input type="text" wire:model="reference" class="champ-compact w-full text-base" required maxlength="100" autocomplete="off">
                        </label>
                        <label class="mb-3 block">
                            <span class="etiquette">NOTE (optionnelle)</span>
                            <input type="text" wire:model="note" class="champ-compact w-full text-base" maxlength="500">
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" wire:loading.attr="disabled" wire:target="enregistrerVersement"
                                    class="btn-mini bg-green-600 text-white">Enregistrer le versement</button>
                            <button type="button" wire:click="ouvrirVersement({{ $g->id }})" class="btn-mini bg-white text-slate-600">Annuler</button>
                        </div>
                    </form>
                @else
                    <button type="button" wire:click="ouvrirVersement({{ $g->id }})" class="btn-mini mt-3 bg-brand text-white">
                        Enregistrer un versement de {{ fcfa($s['du']) }}
                    </button>
                @endif
            @endif
        </x-panneau>
    @empty
        <x-vide titre="Aucun gérant" :sous-titre="$aPayer || $recherche !== '' ? 'Aucun résultat pour ces filtres.' : null" />
    @endforelse

    @if ($historique->isNotEmpty())
        <h2 class="titre-section mb-3 mt-6">Derniers versements</h2>
        <div class="overflow-hidden rounded-[14px] bg-white shadow-carte">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-[13px]">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-[0.5px] text-slate-400">
                        <tr>
                            <th scope="col" class="px-4 py-2.5 font-bold">Date</th>
                            <th scope="col" class="px-4 py-2.5 font-bold">Gérant</th>
                            <th scope="col" class="px-4 py-2.5 text-right font-bold">Montant</th>
                            <th scope="col" class="px-4 py-2.5 font-bold">Compte</th>
                            <th scope="col" class="px-4 py-2.5 font-bold">Référence</th>
                            <th scope="col" class="px-4 py-2.5 font-bold">Par</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($historique as $r)
                            <tr wire:key="reversement-{{ $r->id }}">
                                <td class="px-4 py-2.5 text-slate-500">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2.5 font-semibold text-slate-700">{{ $r->gerant?->raison_sociale }}</td>
                                <td class="px-4 py-2.5 text-right font-bold text-brand">
                                    {{ fcfa($r->montant) }}
                                    <div class="text-[11px] font-normal text-slate-400">{{ $r->nb_reservations }} résa.</div>
                                </td>
                                <td class="px-4 py-2.5 text-slate-500">{{ \App\Models\Gerant::OPERATEURS[$r->momo_operateur] ?? $r->momo_operateur }} · {{ $r->momo_numero }}</td>
                                <td class="px-4 py-2.5 text-slate-500">
                                    {{ $r->reference_operateur }}
                                    @if ($r->note) <div class="text-[11px] text-slate-400">{{ $r->note }}</div> @endif
                                </td>
                                <td class="px-4 py-2.5 text-slate-500">{{ $r->auteur?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
