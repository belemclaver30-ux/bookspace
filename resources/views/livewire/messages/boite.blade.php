@php $client = $user->estClient(); @endphp
<div class="flex flex-1 flex-col" wire:poll.20s>
    <x-entete-profil titre="Messages" :sous-titre="$client ? 'Vos échanges avec les gérants' : 'Vos échanges avec vos clients'" />

    <div @class(['w-full max-w-[640px]', 'px-5 pb-6 pt-4' => $client])>
        @if ($conversations->isEmpty())
            <x-vide titre="Aucun message pour le moment"
                :sous-titre="$client
                    ? 'Pour écrire à un gérant, ouvrez une de vos réservations et touchez « Contacter le gérant ».'
                    : 'Pour écrire à un client, utilisez le bouton « Message » sur une de ses réservations.'" />
        @else
            <div class="divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white shadow-carte">
                @foreach ($conversations as $c)
                    @php
                        $autre = $c->interlocuteur($user);
                        $nom = $client ? ($c->gerant?->raison_sociale ?? 'Gérant') : ($autre?->name ?? 'Client');
                        $dernier = $c->dernierMessage;
                        $deMoi = $dernier?->auteur_id === $user->id;
                        $quand = $c->dernier_message_at;
                    @endphp
                    <a href="/messages/{{ $c->id }}" wire:key="conversation-{{ $c->id }}"
                       @class(['flex items-center gap-3 px-4 py-3.5 hover:bg-slate-50', 'bg-brand-50/60' => $c->non_lus > 0])>
                        @if ($autre)
                            <x-avatar :user="$autre" taille="44" />
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-2">
                                <span @class(['truncate text-sm text-ink', 'font-extrabold' => $c->non_lus > 0, 'font-semibold' => $c->non_lus === 0])>{{ $nom }}</span>
                                <span @class(['shrink-0 text-[11px]', 'font-bold text-brand' => $c->non_lus > 0, 'text-slate-400' => $c->non_lus === 0])>
                                    {{ $quand->isToday() ? $quand->format('H:i') : ($quand->isYesterday() ? 'Hier' : $quand->format('d/m')) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span @class(['truncate text-[13px]', 'font-semibold text-slate-700' => $c->non_lus > 0, 'text-slate-500' => $c->non_lus === 0])>
                                    @if ($deMoi) <span class="text-slate-400">Vous :</span> @endif{{ $dernier?->contenu }}
                                </span>
                                @if ($c->non_lus > 0)
                                    <span class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-brand px-1.5 text-[11px] font-bold text-white"
                                          aria-label="{{ $c->non_lus }} non lu{{ $c->non_lus > 1 ? 's' : '' }}">{{ $c->non_lus > 99 ? '99+' : $c->non_lus }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
