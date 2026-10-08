@props(['title' => null])
{{--
    Coque de l'application cliente.
    - Mobile : colonne plein écran + barre de navigation basse.
    - Bureau (≥ 900 px) : barre latérale à gauche + colonne de contenu centrée.
--}}
@php
    $user = auth()->user();
    $liens = [
        ['/app', 'accueil', 'Accueil', request()->is('app'), 0, null],
        ['/reservations', 'calendrier', 'Réservations', request()->is('reservations*'), 0, null],
        ['/messages', 'message', 'Messages', request()->is('messages*'), app(\App\Services\MessagerieService::class)->nonLus($user), 'messages'],
        ['/notifications', 'cloche', 'Notifications', request()->is('notifications'), $user->notifications()->whereNull('lu_at')->count(), 'notifications'],
        ['/profil', 'utilisateur', 'Profil', request()->is('profil*'), 0, null],
    ];
@endphp
<x-layouts.base :title="$title">
    <div class="flex min-h-dvh justify-center bg-brand-dark">
        <aside class="sticky top-0 hidden h-dvh w-[230px] flex-col bg-brand-dark px-4 py-6 text-white min-[900px]:flex">
            <div class="px-2 pb-5 font-display text-[19px] font-extrabold">BookSpace</div>
            @foreach ($liens as [$url, $icone, $libelle, $actif, $pastille, $compteur])
                <a href="{{ $url }}" @class([
                    'mb-[3px] flex items-center gap-3 rounded-[10px] px-3.5 py-[11px] text-sm font-semibold',
                    'bg-brand text-white' => $actif,
                    'text-white/60 hover:text-white' => ! $actif,
                ]) @if ($actif) aria-current="page" @endif>
                    <x-icone :nom="$icone" taille="22" /><span class="flex-1">{{ $libelle }}</span>
                    <x-pastille :nombre="$pastille" :compteur="$compteur" />
                </a>
            @endforeach
        </aside>

        <div class="relative flex min-h-dvh w-full max-w-[440px] flex-col bg-slate-50 shadow-[0_0_60px_rgba(0,0,0,0.25)]">
            <div class="flex flex-1 flex-col">
                {{ $slot }}
            </div>

            <nav class="sticky bottom-0 flex h-[70px] items-center justify-around border-t border-slate-100 bg-white shadow-[0_-4px_20px_rgba(120,40,15,0.06)] min-[900px]:hidden">
                @foreach ($liens as [$url, $icone, $libelle, $actif, $pastille, $compteur])
                    <a href="{{ $url }}" @class(['flex min-w-0 flex-1 flex-col items-center gap-[3px] py-2', 'text-brand' => $actif, 'text-slate-400' => ! $actif])
                       @if ($actif) aria-current="page" @endif>
                        <span class="relative">
                            <x-icone :nom="$icone" taille="22" />
                            <x-pastille :nombre="$pastille" :compteur="$compteur" class="absolute -right-2.5 -top-1.5 ring-2 ring-white" />
                        </span>
                        <span @class(['max-w-full truncate text-[10px]', 'font-semibold' => $actif])>{{ $libelle }}</span>
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</x-layouts.base>
