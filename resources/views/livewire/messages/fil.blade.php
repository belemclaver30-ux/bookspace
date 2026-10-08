@php
    $client = $user->estClient();
    $jourPrecedent = null;
@endphp
<div @class(['flex flex-1 flex-col', 'min-h-[calc(100dvh-58px-3rem)]' => ! $client]) wire:poll.10s>
    {{-- En-tête : interlocuteur --}}
    <div @class([
        'sticky top-0 z-10 flex items-center gap-3 border-b border-slate-100 bg-white',
        'px-4 pb-3 pt-4' => $client,
        '-mx-4 -mt-4 px-4 py-3 min-[901px]:-mx-6 min-[901px]:-mt-6 min-[901px]:px-6' => ! $client,
    ])>
        <x-retour href="/messages" />
        @if ($interlocuteur)
            <x-avatar :user="$interlocuteur" taille="40" />
        @endif
        <div class="min-w-0">
            <h1 class="truncate text-[15px] font-bold text-brand-dark">{{ $nom }}</h1>
            <p class="truncate text-xs text-slate-400">{{ $client ? 'Gérant' : 'Client' }}@if (! $client && $interlocuteur) · {{ $interlocuteur->telephone }}@endif</p>
        </div>
    </div>

    {{-- Messages --}}
    <div class="flex flex-1 flex-col gap-1.5 px-4 py-4" role="log" aria-live="polite" aria-label="Messages">
        @forelse ($messages as $m)
            @php
                $deMoi = $m->auteur_id === $user->id;
                $jour = $m->created_at->toDateString();
            @endphp
            @if ($jour !== $jourPrecedent)
                @php $jourPrecedent = $jour; @endphp
                <div class="my-2 text-center text-[11px] font-semibold text-slate-400">
                    {{ $m->created_at->isToday() ? "Aujourd'hui" : ($m->created_at->isYesterday() ? 'Hier' : date_longue($m->created_at)) }}
                </div>
            @endif
            <div wire:key="message-{{ $m->id }}" @class(['flex', 'justify-end' => $deMoi, 'justify-start' => ! $deMoi])>
                <div @class([
                    'max-w-[82%] rounded-2xl px-3.5 py-2 shadow-carte',
                    'rounded-br-md bg-brand text-white' => $deMoi,
                    'rounded-bl-md bg-white text-ink' => ! $deMoi,
                ])>
                    @if ($m->reservation)
                        <div @class(['mb-1 rounded-lg px-2 py-1 text-[11px] font-semibold', 'bg-white/15 text-white' => $deMoi, 'bg-brand-50 text-brand-dark' => ! $deMoi])>
                            Réservation {{ $m->reservation->reference }} · {{ $m->reservation->espace?->nom }} · {{ $m->reservation->date_debut->format('d/m H:i') }}
                        </div>
                    @endif
                    <p class="whitespace-pre-line break-words text-sm leading-snug">{{ $m->contenu }}</p>
                    <div @class(['mt-0.5 text-right text-[10px]', 'text-white/70' => $deMoi, 'text-slate-400' => ! $deMoi])>
                        {{ $m->created_at->format('H:i') }}@if ($deMoi) · {{ $m->lu_at ? 'Lu' : 'Envoyé' }}@endif
                    </div>
                </div>
            </div>
        @empty
            <div class="m-auto max-w-[280px] py-10 text-center text-[13px] text-slate-400">
                Aucun message pour l'instant. Écrivez le premier : {{ $client ? 'le gérant' : 'votre client' }} le verra dans sa boîte de réception.
            </div>
        @endforelse
        <div x-data x-init="$nextTick(() => $el.scrollIntoView())" @fil-a-jour.window="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth' }))"></div>
    </div>

    {{-- Saisie --}}
    <form wire:submit="envoyer" @class([
        'sticky bottom-0 border-t border-slate-100 bg-white px-3 pt-2.5 pb-[calc(10px+env(safe-area-inset-bottom))]',
        '-mx-4 -mb-4 min-[901px]:-mx-6 min-[901px]:-mb-6' => ! $client,
    ])>
        @if ($reference)
            <div class="mb-2 flex items-center gap-2 rounded-lg bg-brand-50 px-3 py-1.5 text-xs text-brand-dark">
                <span class="flex-1 truncate">À propos de la réservation <b>{{ $reference->reference }}</b> · {{ $reference->espace?->nom }}</span>
                <button type="button" wire:click="retirerReference" class="font-bold" aria-label="Retirer la référence à la réservation">×</button>
            </div>
        @endif
        @error('contenu') <p class="mb-1.5 text-xs font-medium text-red-600" role="alert">{{ $message }}</p> @enderror
        <div class="flex items-end gap-2">
            <label class="flex-1">
                <span class="sr-only">Votre message</span>
                <textarea wire:model="contenu" rows="1" maxlength="{{ $longueurMax }}" placeholder="Écrire un message…"
                          x-data x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 140) + 'px'"
                          x-on:keydown.enter="if (! $event.shiftKey && window.matchMedia('(min-width: 900px)').matches) { $event.preventDefault(); $el.form.requestSubmit() }"
                          class="block max-h-[140px] w-full resize-none rounded-2xl border-[1.5px] border-slate-200 bg-slate-50 px-3.5 py-2.5 leading-snug text-ink outline-none focus:border-brand"></textarea>
            </label>
            <button type="submit" wire:loading.attr="disabled" wire:target="envoyer" aria-label="Envoyer"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand text-white disabled:bg-slate-300">
                <x-icone nom="envoyer" taille="20" />
            </button>
        </div>
    </form>
</div>
