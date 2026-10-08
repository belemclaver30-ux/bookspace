@php
    $client = $user->estClient();
    // Icône et couleur selon le type de notification.
    $style = function (string $type): array {
        return match (true) {
            str_contains($type, 'confirmee'), str_contains($type, 'acceptee') => ['coche', 'bg-green-100 text-green-600'],
            str_contains($type, 'annulee'), str_contains($type, 'expiree'), str_contains($type, 'refusee') => ['alerte', 'bg-red-100 text-red-600'],
            str_contains($type, 'rappel') => ['cloche', 'bg-[#FEF0D6] text-attente'],
            str_contains($type, 'litige') => ['drapeau', 'bg-slate-100 text-slate-600'],
            str_contains($type, 'demande') => ['message', 'bg-brand-50 text-brand-dark'],
            default => ['calendrier', 'bg-brand-50 text-brand'],
        };
    };
@endphp
<div class="flex flex-1 flex-col">
    <x-entete-profil titre="Notifications"
        :sous-titre="$nonLues > 0 ? $nonLues . ' non lue' . ($nonLues > 1 ? 's' : '') : 'Tout est lu'" />

    <div @class(['w-full max-w-[640px]', 'px-4 pb-6 pt-4' => $client])>
        @if ($nonLues > 0)
            <div class="mb-3 flex justify-end">
                <button type="button" wire:click="toutMarquerLu" class="rounded-full bg-white px-3.5 py-1.5 text-xs font-semibold text-brand shadow-carte">
                    Tout marquer comme lu
                </button>
            </div>
        @endif

        @forelse ($groupes as $periode => $notifications)
            <h2 class="mb-2 mt-1 pl-1 text-[11px] font-bold uppercase tracking-[0.5px] text-slate-400">{{ $periode }}</h2>
            <div class="mb-4 flex flex-col gap-2">
                @foreach ($notifications as $n)
                    @php
                        [$icone, $couleur] = $style($n->type);
                        $ouvert = $ouverte === $n->id;
                        $nonLue = $n->lu_at === null;
                        $lien = $liens[$n->id] ?? null;
                    @endphp
                    <div wire:key="notif-{{ $n->id }}" @class([
                        'overflow-hidden rounded-2xl bg-white shadow-carte transition-shadow',
                        'ring-2 ring-brand/70' => $nonLue,
                        'shadow-carte-lg' => $ouvert,
                    ])>
                        <button type="button" wire:click="basculer({{ $n->id }})" aria-expanded="{{ $ouvert ? 'true' : 'false' }}"
                                aria-controls="notif-detail-{{ $n->id }}" class="flex w-full items-start gap-3 p-3.5 text-left">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $couleur }}">
                                <x-icone :nom="$icone" taille="20" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-start justify-between gap-2">
                                    <span @class(['text-sm text-brand-dark', 'font-extrabold' => $nonLue, 'font-semibold' => ! $nonLue])>{{ $n->titre }}</span>
                                    <span class="shrink-0 pt-0.5 text-[11px] text-slate-400">
                                        {{ $n->created_at->isToday() ? $n->created_at->format('H:i') : $n->created_at->format('d/m') }}
                                    </span>
                                </span>
                                @unless ($ouvert)
                                    <span class="mt-0.5 block truncate text-[13px] text-slate-500">{{ $n->contenu }}</span>
                                @endunless
                            </span>
                            @if ($nonLue)
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-brand" aria-label="Non lue"></span>
                            @endif
                            <x-icone nom="chevron" taille="16" :class="'mt-1 text-slate-400 transition-transform' . ($ouvert ? ' rotate-90' : '')" />
                        </button>

                        @if ($ouvert)
                            <div id="notif-detail-{{ $n->id }}" class="animate-fadeinup border-t border-slate-100 px-4 pb-4 pt-3 pl-[66px]">
                                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $n->contenu }}</p>
                                <p class="mt-2 text-[11px] text-slate-400">
                                    {{ date_longue($n->created_at) }} à {{ $n->created_at->format('H:i') }}
                                    @if ($n->canal === 'sms') · également envoyée par SMS @endif
                                </p>
                                @if ($lien)
                                    <a href="{{ $lien[1] }}" class="mt-3 inline-flex items-center gap-1 rounded-full bg-brand px-4 py-2 text-[13px] font-semibold text-white">
                                        {{ $lien[0] }} <x-icone nom="chevron" taille="14" />
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @empty
            <x-vide titre="Aucune notification" sous-titre="Vous serez prévenu ici de chaque étape de vos réservations." />
        @endforelse
    </div>
</div>
