@php $initiales = ['D', 'L', 'M', 'M', 'J', 'V', 'S']; @endphp
<div class="flex flex-1 flex-col">
    <div class="flex items-center gap-3 border-b border-slate-100 bg-white px-5 pb-4 pt-5">
        <x-retour href="/espaces/{{ $espace->id }}" />
        <div>
            <h1 class="font-display text-[17px] font-bold text-brand-dark">Choisir un créneau</h1>
            <div class="text-xs text-slate-500">{{ $espace->nom }}</div>
        </div>
    </div>

    <div class="flex-1 px-5 py-4">
        @if ($abonnementDisponible)
            <div class="mb-5 flex gap-2 rounded-xl bg-slate-100 p-1">
                <button type="button" wire:click="choisirMode('ponctuelle')" aria-pressed="{{ $mode === 'ponctuelle' ? 'true' : 'false' }}"
                        @class(['flex-1 rounded-[10px] py-2 text-[13px] font-semibold', 'bg-white text-brand shadow-carte' => $mode === 'ponctuelle', 'text-slate-500' => $mode !== 'ponctuelle'])>
                    Séance unique
                </button>
                <button type="button" wire:click="choisirMode('abonnement')" aria-pressed="{{ $mode === 'abonnement' ? 'true' : 'false' }}"
                        @class(['flex-1 rounded-[10px] py-2 text-[13px] font-semibold', 'bg-white text-brand shadow-carte' => $mode === 'abonnement', 'text-slate-500' => $mode !== 'abonnement'])>
                    Abonnement
                </button>
            </div>
        @endif

        @if ($mode === 'abonnement')
            <div class="titre-section mb-2.5 text-[13px]">Formule</div>
            <div class="mb-5 flex flex-col gap-2">
                @foreach ($formules as $f)
                    <button type="button" wire:click="choisirFormule({{ $f->id }})" wire:key="formule-{{ $f->id }}"
                            aria-pressed="{{ $formuleId === $f->id ? 'true' : 'false' }}" @class([
                        'flex items-center justify-between rounded-xl p-3 text-left',
                        'border-2 border-brand bg-brand-50' => $formuleId === $f->id,
                        'border-[1.5px] border-slate-200 bg-white' => $formuleId !== $f->id,
                    ])>
                        <span class="text-[13px] font-semibold text-brand-dark">{{ $f->nombre_seances }} séances</span>
                        <span class="text-[13px] font-bold text-brand">{{ fcfa($f->prix) }}</span>
                    </button>
                @endforeach
            </div>
            <p class="-mt-3 mb-5 text-xs text-slate-400">Une séance par semaine, à partir de la première date choisie ci-dessous.</p>
        @endif

        <div class="titre-section mb-2.5 text-[13px]">Date {{ $mode === 'abonnement' ? 'de la première séance' : '' }}</div>
        <div class="mb-5 flex gap-1.5 overflow-x-auto pb-1.5">
            @foreach ($jours as $j)
                @php $actif = $j->toDateString() === $date; @endphp
                <button type="button" wire:click="choisirDate('{{ $j->toDateString() }}')" wire:key="jour-{{ $j->toDateString() }}"
                        aria-pressed="{{ $actif ? 'true' : 'false' }}" aria-label="{{ date_longue($j) }}"
                        @class(['min-w-[46px] rounded-[14px] px-1 py-2.5 shadow-carte', 'bg-brand-dark' => $actif, 'bg-white' => ! $actif])>
                    <div @class(['mb-1 text-[10px] font-medium', 'text-white/60' => $actif, 'text-slate-400' => ! $actif])>{{ $initiales[$j->dayOfWeek] }}</div>
                    <div @class(['text-[15px] font-bold', 'text-white' => $actif, 'text-slate-700' => ! $actif])>{{ $j->day }}</div>
                </button>
            @endforeach
        </div>

        <div class="titre-section mb-2.5 text-[13px]">Durée</div>
        <div class="mb-5 flex gap-2">
            @foreach ($durees as $h)
                <button type="button" wire:click="choisirDuree({{ $h }})" aria-pressed="{{ $duree === $h ? 'true' : 'false' }}" @class([
                    'flex-1 rounded-xl p-2.5 text-[13px] font-semibold',
                    'border-2 border-brand bg-brand-50 text-brand' => $duree === $h,
                    'border-[1.5px] border-slate-200 bg-white text-slate-500' => $duree !== $h,
                ])>{{ $h }}h</button>
            @endforeach
        </div>

        <div class="titre-section mb-2.5 text-[13px]">Créneaux disponibles</div>
        <div class="mb-[22px] grid grid-cols-4 gap-2" wire:loading.class="opacity-50" wire:target="choisirDate, choisirDuree">
            @forelse ($creneaux as $c)
                <button type="button" wire:click="choisirCreneau('{{ $c['heure'] }}')" wire:key="creneau-{{ $c['heure'] }}"
                        @disabled(! $c['disponible']) aria-pressed="{{ $creneau === $c['heure'] ? 'true' : 'false' }}" @class([
                    'rounded-xl px-1 py-2.5 text-[13px] font-semibold',
                    'bg-slate-100 text-slate-300' => ! $c['disponible'],
                    'bg-brand text-white shadow-carte' => $c['disponible'] && $creneau === $c['heure'],
                    'bg-white text-slate-700 shadow-carte' => $c['disponible'] && $creneau !== $c['heure'],
                ])>{{ $c['heure'] }}</button>
            @empty
                <div class="col-span-full text-[13px] text-slate-400">Fermé ce jour-là.</div>
            @endforelse
        </div>

        <div class="rounded-2xl bg-white p-4 shadow-carte-lg">
            <div class="titre-section mb-3 text-[13px]">Résumé</div>
            <x-ligne libelle="Date" :valeur="date_longue($jour)" />
            <x-ligne libelle="Heure" :valeur="$creneau ? $creneau . ' – ' . \Carbon\CarbonImmutable::parse($creneau)->addHours($duree)->format('H:i') : '—'" />
            <x-ligne libelle="Durée" :valeur="$duree . ' heure' . ($duree > 1 ? 's' : '')" />
            @if ($mode === 'abonnement')
                <x-ligne libelle="Séances" :valeur="$formule ? $formule->nombre_seances . ', une par semaine' : '—'" />
                <x-ligne libelle="Prix du pack" :valeur="$formule ? fcfa($formule->prix) : '—'" />
                <x-ligne :libelle="'Acompte (' . heures_lisibles($tauxAcompte) . '%)'" :valeur="fcfa($acompteFormule)" />
                <div class="my-2.5 h-px bg-slate-100"></div>
                <div class="flex justify-between text-sm font-bold">
                    <span class="text-brand-dark">À payer maintenant</span>
                    <span class="text-brand">{{ fcfa($acompteFormule) }}</span>
                </div>
            @else
                <x-ligne libelle="Prix total" :valeur="fcfa($prixTotal)" />
                <x-ligne :libelle="'Acompte (' . heures_lisibles($tauxAcompte) . '%)'" :valeur="fcfa($acompte)" />
                <div class="my-2.5 h-px bg-slate-100"></div>
                <div class="flex justify-between text-sm font-bold">
                    <span class="text-brand-dark">À payer maintenant</span>
                    <span class="text-brand">{{ fcfa($acompte) }}</span>
                </div>
            @endif
        </div>

        <div class="h-3.5"></div>
        <x-erreur />
    </div>

    <div class="sticky bottom-0 mt-auto border-t border-slate-100 bg-white px-5 pb-[calc(14px+env(safe-area-inset-bottom))] pt-3.5">
        @if ($mode === 'abonnement')
            <button type="button" class="btn-primaire" wire:click="sabonner" @disabled(! $creneau || ! $formuleId) wire:loading.attr="disabled" wire:target="sabonner">
                <span wire:loading.remove wire:target="sabonner">Continuer</span>
                <span wire:loading wire:target="sabonner">Traitement…</span>
            </button>
        @else
            <button type="button" class="btn-primaire" wire:click="reserver" @disabled(! $creneau) wire:loading.attr="disabled" wire:target="reserver">
                <span wire:loading.remove wire:target="reserver">Continuer</span>
                <span wire:loading wire:target="reserver">Traitement…</span>
            </button>
        @endif
    </div>
</div>
