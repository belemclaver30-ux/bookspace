<div class="flex flex-1 flex-col items-center px-7 pb-8">
    <div class="flex flex-1 animate-fadeinup flex-col items-center py-8 text-center">
        <div class="mb-6 flex h-[88px] w-[88px] items-center justify-center rounded-full bg-green-600 text-white shadow-[0_12px_40px_rgba(22,163,74,0.4)]">
            <x-icone nom="coche" taille="28" />
        </div>
        <h1 class="mb-2 font-display text-2xl font-extrabold text-brand-dark">Abonnement confirmé !</h1>
        <p class="mb-7 text-sm leading-relaxed text-slate-500">
            Vos {{ $a->nombre_seances }} séances sont bloquées. Une confirmation a été envoyée au gérant et à vous-même.
        </p>

        <div class="w-full rounded-2xl bg-slate-50 p-[18px] text-left">
            <x-ligne libelle="Référence" :valeur="$a->reference" />
            <x-ligne libelle="Espace" :valeur="$a->espace?->nom ?? '—'" />
            <x-ligne libelle="Séances" :valeur="$a->nombre_seances . ', une par semaine'" />
            <x-ligne libelle="Acompte payé" :valeur="fcfa($a->montant_acompte)" />
            <x-ligne libelle="Reste à régler sur place (toutes séances)" :valeur="fcfa($a->prix_total - $a->montant_acompte)" />
        </div>

        <div class="mt-5 w-full rounded-2xl bg-slate-50 p-[18px] text-left">
            <div class="mb-2 text-[11px] font-bold uppercase tracking-[0.5px] text-slate-400">Dates des séances</div>
            @foreach ($a->reservations as $r)
                <div class="flex items-center justify-between border-b border-slate-100 py-2 text-[13px] text-slate-700 last:border-b-0">
                    <span>{{ date_longue($r->date_debut) }}</span>
                    <span class="text-slate-400">{{ plage_horaire($r->date_debut, $r->date_fin) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex w-full flex-col gap-3">
        <a href="/abonnements" class="btn-primaire">Voir mes abonnements</a>
        <a href="/app" class="btn-contour">Retour à l'accueil</a>
    </div>
</div>
