<div class="flex flex-1 flex-col items-center px-7 pb-8">
    <div class="flex flex-1 animate-fadeinup flex-col items-center justify-center py-8 text-center">
        <div class="mb-6 flex h-[88px] w-[88px] items-center justify-center rounded-full bg-green-600 text-white shadow-[0_12px_40px_rgba(22,163,74,0.4)]">
            <x-icone nom="coche" taille="28" />
        </div>
        <h1 class="mb-2 font-display text-2xl font-extrabold text-brand-dark">Réservation confirmée !</h1>
        <p class="mb-7 text-sm leading-relaxed text-slate-500">
            Votre créneau est bloqué. Une confirmation a été envoyée au gérant et à vous-même.
        </p>

        <div class="w-full rounded-2xl bg-slate-50 p-[18px] text-left">
            <x-ligne libelle="Référence" :valeur="$r->reference" />
            <x-ligne libelle="Espace" :valeur="$r->espace?->nom ?? '—'" />
            <x-ligne libelle="Date" :valeur="date_longue($r->date_debut)" />
            <x-ligne libelle="Horaire" :valeur="plage_horaire($r->date_debut, $r->date_fin)" />
            <x-ligne libelle="Acompte payé" :valeur="fcfa($r->montant_acompte)" />
            <x-ligne libelle="Reste à régler sur place" :valeur="fcfa($r->prix_total - $r->montant_acompte)" />
        </div>
    </div>

    <div class="flex w-full flex-col gap-3">
        <a href="/reservations" class="btn-primaire">Voir mes réservations</a>
        <a href="/app" class="btn-contour">Retour à l'accueil</a>
    </div>
</div>
