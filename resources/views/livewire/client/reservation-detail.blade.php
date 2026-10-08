<div class="flex flex-1 flex-col">
    <div class="flex items-center gap-3 border-b border-slate-100 bg-white px-5 pb-4 pt-5">
        <x-retour href="/reservations" />
        <h1 class="font-display text-[17px] font-bold text-brand-dark">{{ $r->reference }}</h1>
        <div class="ml-auto"><x-badge-statut :statut="$r->statut" /></div>
    </div>

    <div class="px-5 pb-10 pt-[18px]">
        <div class="mb-4 rounded-2xl bg-white p-4 shadow-carte-lg">
            <x-ligne libelle="Espace" :valeur="$r->espace?->nom ?? '—'" />
            <x-ligne libelle="Adresse" :valeur="trim(($r->espace?->adresse ?? '') . ' ' . ($r->espace?->ville ?? ''))" />
            <x-ligne libelle="Date" :valeur="date_longue($r->date_debut)" />
            <x-ligne libelle="Horaire" :valeur="plage_horaire($r->date_debut, $r->date_fin)" />
            <x-ligne libelle="Durée" :valeur="heures_lisibles($r->duree_heures) . ' h'" />
        </div>

        <div class="mb-4 rounded-2xl bg-white p-4 shadow-carte-lg">
            <x-ligne libelle="Prix total" :valeur="fcfa($r->prix_total)" />
            <x-ligne :libelle="'Acompte (' . heures_lisibles($r->taux_acompte) . '%)'" :valeur="fcfa($r->montant_acompte)" />
            <x-ligne libelle="Reste sur place" :valeur="fcfa($r->prix_total - $r->montant_acompte)" />
            @if ($r->paiement)
                <x-ligne libelle="Paiement" :valeur="str_replace('_', ' ', $r->paiement->mode) . ' · ' . str_replace('_', ' ', $r->paiement->statut)" />
            @endif
        </div>

        <a href="/messages/reservation/{{ $r->id }}" class="mb-4 flex items-center gap-3 rounded-2xl bg-white px-4 py-3.5 shadow-carte-lg">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand"><x-icone nom="message" taille="20" /></span>
            <span class="flex-1">
                <span class="block text-sm font-semibold text-ink">Contacter le gérant</span>
                <span class="block text-xs text-slate-500">Une question sur cette réservation ? Écrivez-lui.</span>
            </span>
            <x-icone nom="chevron" taille="16" class="text-slate-400" />
        </a>

        @if ($r->motif_annulation)
            <div class="mb-4 rounded-xl bg-red-50 p-3.5 text-[13px] text-red-700">Motif d'annulation : {{ $r->motif_annulation }}</div>
        @endif

        <x-erreur />

        {{-- ── Demande en attente de validation par le gérant ── --}}
        @if ($demande?->statut === 'en_attente')
            <div class="mb-3 rounded-[14px] bg-[#FEF0D6] p-4">
                <div class="text-[13px] font-bold text-[#854D0E]">
                    Demande {{ $demande->type === 'annulation' ? "d'annulation" : 'de report' }} envoyée
                </div>
                <div class="mt-1 text-xs text-[#92700F]">
                    En attente de la validation du gérant.
                    @if ($demande->type === 'report' && $demande->nouvelle_date_debut)
                        Nouveau créneau proposé :
                        <b>{{ date_longue($demande->nouvelle_date_debut) }} à {{ $demande->nouvelle_date_debut->format('H:i') }}</b>.
                    @endif
                </div>
                @if ($demande->motif)
                    <div class="mt-1 text-xs text-[#92700F]">Votre motif : « {{ $demande->motif }} »</div>
                @endif
                <button type="button" wire:click="retirerDemande" wire:loading.attr="disabled"
                        class="mt-2.5 rounded-[9px] border-[1.5px] border-yellow-500 bg-white px-3.5 py-[7px] text-[12.5px] font-bold text-[#854D0E]">
                    Retirer la demande
                </button>
            </div>
        @elseif ($r->estAnnulable() || $demande)
            <div class="mb-3">
                @if ($demande?->statut === 'refusee')
                    <div class="mb-2.5 rounded-[14px] bg-red-100 p-3.5 text-[12.5px] text-red-700">
                        Le gérant a refusé votre demande {{ $demande->type === 'annulation' ? "d'annulation" : 'de report' }}.
                        @if ($demande->reponse_gerant) Message : « {{ $demande->reponse_gerant }} » @endif
                    </div>
                @endif

                {{-- Réservation non confirmée : annulation directe --}}
                @if ($r->statut === 'en_attente' && $r->estAnnulable())
                    <a href="/reservations/{{ $r->id }}/paiement" class="btn-primaire mb-2.5">Payer l'acompte</a>
                    <button type="button" class="btn-contour" wire:click="annuler" wire:confirm="Annuler cette réservation ?" wire:loading.attr="disabled">
                        Annuler la réservation
                    </button>
                @endif

                {{-- Réservation confirmée : demandes soumises au gérant --}}
                @if ($r->statut === 'confirmee' && $r->estAnnulable())
                    @if ($mode === '')
                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="ouvrir('report')" class="btn-mini bg-brand-50 text-brand-dark">Demander un report</button>
                            <button type="button" wire:click="ouvrir('annulation')" class="btn-mini bg-red-100 text-red-600">Demander l'annulation</button>
                        </div>
                    @else
                        <form wire:submit="envoyerDemande" class="carte">
                            <div class="titre-section mb-2 text-[13px]">Demande {{ $mode === 'annulation' ? "d'annulation" : 'de report' }}</div>

                            @if ($mode === 'report')
                                <div class="mb-2.5 flex flex-wrap gap-2">
                                    <label class="flex-[1_1_170px]">
                                        <span class="sr-only">Nouveau créneau</span>
                                        <input type="datetime-local" wire:model="date" class="champ-compact w-full" required>
                                    </label>
                                    <label>
                                        <span class="sr-only">Durée</span>
                                        <select wire:model="duree" class="champ-compact">
                                            @foreach (range(1, 6) as $h)
                                                <option value="{{ $h }}">{{ $h }} h</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                            @endif

                            <label>
                                <span class="sr-only">Motif</span>
                                <textarea wire:model="motif" rows="2" maxlength="500" class="champ-compact mb-2.5 w-full resize-y"
                                          placeholder="Motif (facultatif, transmis au gérant)"></textarea>
                            </label>

                            <div class="flex gap-2">
                                <button type="submit" wire:loading.attr="disabled"
                                        @class(['btn-mini flex-1 text-white', 'bg-red-600' => $mode === 'annulation', 'bg-brand' => $mode === 'report'])>
                                    Envoyer la demande
                                </button>
                                <button type="button" wire:click="ouvrir('')" class="btn-mini bg-slate-100 text-slate-600">Annuler</button>
                            </div>
                        </form>
                    @endif
                @endif
            </div>
        @endif

        {{-- ── Avis ── --}}
        @if ($r->evaluation)
            @if ($avisEnvoye)
                <div class="mb-3 flex items-center gap-2 rounded-xl bg-green-100 p-3.5 text-[13px] font-semibold text-green-700">
                    <x-icone nom="etoile" taille="13" plein class="text-gold" /> Merci pour votre avis !
                </div>
            @endif
            <div class="rounded-2xl bg-white p-4 shadow-carte-lg">
                <div class="titre-section mb-2">Votre avis</div>
                <div class="flex gap-[3px] text-xl" aria-label="{{ $r->evaluation->note }} sur 5">
                    @for ($n = 1; $n <= 5; $n++)
                        <span @class(['text-gold' => $n <= $r->evaluation->note, 'text-slate-200' => $n > $r->evaluation->note])>★</span>
                    @endfor
                </div>
                @if ($r->evaluation->commentaire)
                    <div class="mt-2 text-[13px] leading-relaxed text-slate-500">« {{ $r->evaluation->commentaire }} »</div>
                @endif
            </div>
        @elseif ($r->statut === 'terminee')
            <form wire:submit="evaluer" class="rounded-2xl bg-white p-4 shadow-carte-lg">
                <div class="titre-section mb-1">Évaluer cet espace</div>
                <div class="mb-3 text-xs text-slate-400">Votre séjour est terminé — partagez votre expérience.</div>
                <div class="mb-3 flex gap-1.5" role="radiogroup" aria-label="Note">
                    @for ($n = 1; $n <= 5; $n++)
                        <button type="button" wire:click="$set('note', {{ $n }})" role="radio" aria-checked="{{ $note === $n ? 'true' : 'false' }}"
                                aria-label="{{ $n }} étoile{{ $n > 1 ? 's' : '' }}"
                                @class(['p-0 text-3xl leading-none', 'text-gold' => $n <= $note, 'text-slate-200' => $n > $note])>★</button>
                    @endfor
                </div>
                <label>
                    <span class="sr-only">Commentaire</span>
                    <textarea wire:model="commentaire" rows="3" maxlength="1000" class="champ mb-3 resize-y"
                              placeholder="Votre commentaire (optionnel)"></textarea>
                </label>
                <button type="submit" class="btn-primaire" wire:loading.attr="disabled">Publier mon avis</button>
            </form>
        @endif
    </div>
</div>
