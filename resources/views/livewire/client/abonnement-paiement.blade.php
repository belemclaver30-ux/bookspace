<div class="flex flex-1 flex-col" @if ($verification) wire:poll.3s="sonder" @endif>
    <div class="flex items-center gap-3 border-b border-slate-100 bg-white px-5 pb-4 pt-5">
        <x-retour href="/abonnements" />
        <h1 class="font-display text-[17px] font-bold text-brand-dark">Paiement de l'acompte</h1>
    </div>

    <div class="flex-1 px-5 pb-8 pt-5">
        <div class="mb-6 rounded-[18px] bg-brand-dark p-5 text-center">
            <div class="mb-1.5 text-xs font-medium text-white/50">Montant à payer</div>
            <div class="font-display text-[32px] font-extrabold text-white">{{ fcfa($a->montant_acompte) }}</div>
            <div class="mt-1 text-xs text-white/45">Acompte {{ heures_lisibles($a->taux_acompte) }}% · {{ $a->espace?->nom }} · {{ $a->nombre_seances }} séances</div>
        </div>

        <div class="titre-section mb-3 text-[13px]">Moyen de paiement</div>
        <div class="mb-3 flex items-center gap-3.5 rounded-2xl border-[1.5px] border-slate-200 bg-white p-4">
            <div class="flex h-[46px] w-[46px] items-center justify-center rounded-xl bg-brand text-[15px] font-black text-white">Y</div>
            <div class="flex-1">
                <div class="text-sm font-bold text-brand-dark">Yenga</div>
                <div class="text-xs text-slate-400">Orange Money, Moov Money, Telecel Money…</div>
            </div>
        </div>
        <p class="px-0.5 text-xs leading-normal text-slate-500">
            Vous serez redirigé vers la page de paiement sécurisée Yenga pour choisir votre opérateur et confirmer.
        </p>

        <div class="mt-5 rounded-[14px] bg-slate-50 p-3.5 text-xs">
            <div class="mb-2 flex justify-between">
                <span class="text-slate-500">{{ $a->espace?->nom }} · {{ $a->nombre_seances }} séances</span>
                <span class="font-semibold text-slate-700">{{ fcfa($a->prix_total) }}</span>
            </div>
            <div class="mb-2 flex justify-between">
                <span class="text-slate-500">Acompte ({{ heures_lisibles($a->taux_acompte) }}%)</span>
                <span class="font-semibold text-slate-700">{{ fcfa($a->montant_acompte) }}</span>
            </div>
            <div class="my-2 h-px bg-slate-200"></div>
            <div class="flex justify-between font-bold">
                <span class="text-brand-dark">Total</span>
                <span class="text-brand">{{ fcfa($a->montant_acompte) }}</span>
            </div>
        </div>

        @if ($paiementEnCours)
            <button type="button" wire:click="verifier" @disabled($verification)
                    class="mt-4 w-full rounded-[14px] border-[1.5px] border-slate-200 bg-white p-[13px] text-[13.5px] font-bold text-brand-dark">
                {{ $verification ? 'Vérification…' : "J'ai terminé mon paiement, vérifier" }}
            </button>
        @endif

        <div class="h-3.5"></div>
        <x-erreur />
    </div>

    <div class="sticky bottom-0 mt-auto border-t border-slate-100 bg-white px-5 pb-[calc(14px+env(safe-area-inset-bottom))] pt-3.5">
        <button type="button" class="btn-primaire" wire:click="payer" wire:loading.attr="disabled" wire:target="payer">
            <span wire:loading.remove wire:target="payer">{{ $paiementEnCours ? 'Reprendre le paiement Yenga' : 'Payer ' . fcfa($a->montant_acompte) }}</span>
            <span wire:loading wire:target="payer">Redirection…</span>
        </button>
    </div>
</div>
