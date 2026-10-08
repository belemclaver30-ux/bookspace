@php
    $client = $user->estClient();
    $etats = [
        'ouvert' => ['bg-[#FEF0D6] text-attente', 'Envoyé'],
        'en_cours' => ['bg-brand-50 text-brand-dark', 'Pris en charge'],
        'resolu' => ['bg-green-100 text-green-600', 'Résolu'],
        'ferme' => ['bg-slate-100 text-slate-500', 'Clos'],
    ];
    $whatsapp = isset($support['whatsapp']) ? preg_replace('/\D+/', '', $support['whatsapp']) : null;
    $titreSection = 'mb-2 pl-1 text-[11px] font-bold uppercase tracking-[0.5px] text-slate-400';
@endphp
<div class="flex flex-1 flex-col">
    <x-entete-profil titre="Centre d'aide" sous-titre="Questions fréquentes et contact" />

    <div @class(['w-full max-w-[640px]', 'px-5 pb-6 pt-4' => $client])>

        {{-- ── Questions fréquentes ── --}}
        <h2 class="{{ $titreSection }}">Questions fréquentes</h2>
        <div class="mb-5 divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white shadow-carte">
            @foreach ($questions as [$question, $reponse])
                <details class="group">
                    <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-3.5 text-sm font-semibold text-ink [&::-webkit-details-marker]:hidden">
                        <span class="flex-1">{{ $question }}</span>
                        <x-icone nom="chevron" taille="16" class="text-slate-400 transition-transform group-open:rotate-90" />
                    </summary>
                    <p class="px-4 pb-4 text-[13px] leading-relaxed text-slate-500">{{ $reponse }}</p>
                </details>
            @endforeach
        </div>

        {{-- ── Contacter le support ── --}}
        <h2 class="{{ $titreSection }}">Contacter le support</h2>
        <div class="mb-2 divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white shadow-carte">
            @if ($whatsapp)
                <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 px-4 py-3.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-50 text-green-600"><x-icone nom="message" /></span>
                    <span class="flex-1"><span class="block text-sm font-medium text-ink">WhatsApp</span><span class="block text-xs text-slate-500">{{ $support['whatsapp'] }}</span></span>
                    <x-icone nom="chevron" taille="16" class="text-slate-400" />
                </a>
            @endif
            @isset($support['telephone'])
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $support['telephone']) }}" class="flex items-center gap-3 px-4 py-3.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-dark"><x-icone nom="telephone" /></span>
                    <span class="flex-1"><span class="block text-sm font-medium text-ink">Appeler</span><span class="block text-xs text-slate-500">{{ $support['telephone'] }}</span></span>
                    <x-icone nom="chevron" taille="16" class="text-slate-400" />
                </a>
            @endisset
            @isset($support['email'])
                <a href="mailto:{{ $support['email'] }}" class="flex items-center gap-3 px-4 py-3.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-500"><x-icone nom="enveloppe" /></span>
                    <span class="flex-1"><span class="block text-sm font-medium text-ink">Écrire un e-mail</span><span class="block text-xs text-slate-500">{{ $support['email'] }}</span></span>
                    <x-icone nom="chevron" taille="16" class="text-slate-400" />
                </a>
            @endisset
        </div>
        @isset($support['horaires'])
            <p class="mb-5 px-1 text-xs text-slate-500">{{ $support['horaires'] }}.</p>
        @else
            <div class="mb-5"></div>
        @endisset

        {{-- ── Signaler un problème sur une réservation ── --}}
        @unless ($user->estAdmin())
            <h2 id="signalement" class="{{ $titreSection }} scroll-mt-20">Signaler un problème</h2>
            <form wire:submit="signaler" class="mb-5 rounded-2xl bg-white p-4 shadow-carte">
                <p class="mb-3 text-xs leading-normal text-slate-500">
                    @if ($user->estGerant())
                        Client absent, dégradation, désaccord sur un paiement… Décrivez la situation : l'équipe BookSpace intervient auprès du client.
                    @else
                        Espace fermé à votre arrivée, équipement manquant, paiement non pris en compte… Décrivez la situation : l'équipe BookSpace intervient auprès du gérant.
                    @endif
                </p>

                @if ($envoye)
                    <div class="mb-3.5 rounded-xl bg-green-100 px-3.5 py-2.5 text-[13px] font-semibold text-green-700" role="status">
                        ✓ Signalement envoyé. Vous serez prévenu dès qu'il sera traité.
                    </div>
                @endif
                <x-erreur />

                @if ($reservations->isEmpty())
                    <p class="text-[13px] text-slate-400">Vous n'avez pas encore de réservation à signaler. Pour toute autre question, contactez le support.</p>
                @else
                    <label class="mb-3 block">
                        <span class="etiquette">RÉSERVATION CONCERNÉE</span>
                        <select wire:model="reservationId" class="champ" required>
                            <option value="">Choisir une réservation…</option>
                            @foreach ($reservations as $r)
                                <option value="{{ $r->id }}">{{ $r->espace?->nom }} — {{ $r->date_debut->format('d/m/Y H:i') }} ({{ $r->reference }})</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="mb-3 block">
                        <span class="etiquette">SUJET</span>
                        <input type="text" wire:model="sujet" class="champ" maxlength="255" placeholder="Ex. Salle fermée à mon arrivée" required>
                    </label>
                    <label class="mb-3 block">
                        <span class="etiquette">DESCRIPTION</span>
                        <textarea wire:model="description" rows="4" maxlength="2000" class="champ resize-y" placeholder="Que s'est-il passé ? Donnez un maximum de détails." required></textarea>
                    </label>
                    <button type="submit" class="btn-primaire" wire:loading.attr="disabled" wire:target="signaler">
                        <span wire:loading.remove wire:target="signaler">Envoyer le signalement</span>
                        <span wire:loading wire:target="signaler">Envoi…</span>
                    </button>
                @endif
            </form>
        @endunless

        {{-- ── Suivi des signalements ── --}}
        @if ($signalements->isNotEmpty())
            <h2 class="{{ $titreSection }}">Mes signalements</h2>
            <div class="mb-5 flex flex-col gap-3">
                @foreach ($signalements as $s)
                    @php [$classes, $libelle] = $etats[$s->statut] ?? $etats['ouvert']; @endphp
                    <div wire:key="signalement-{{ $s->id }}" class="rounded-2xl bg-white p-4 shadow-carte">
                        <div class="mb-1 flex items-start justify-between gap-3">
                            <div class="text-sm font-bold text-brand-dark">{{ $s->sujet }}</div>
                            <span class="shrink-0 rounded-full px-[9px] py-[3px] text-[11px] font-semibold {{ $classes }}">{{ $libelle }}</span>
                        </div>
                        <div class="mb-1.5 text-xs text-slate-400">
                            {{ $s->reservation?->espace?->nom }} · {{ $s->reservation?->reference }} · {{ date_longue($s->created_at) }}
                        </div>
                        <p class="text-[13px] leading-relaxed text-slate-500">{{ $s->description }}</p>
                        @if ($s->resolution)
                            <div class="mt-2 rounded-lg bg-green-50 px-3 py-2 text-[13px] text-green-700">Réponse de BookSpace : {{ $s->resolution }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="flex flex-wrap gap-x-4 gap-y-1 px-1 text-xs font-semibold text-brand">
            <a href="/conditions">Conditions d'utilisation</a>
            <a href="/confidentialite">Politique de confidentialité</a>
        </div>
    </div>
</div>
