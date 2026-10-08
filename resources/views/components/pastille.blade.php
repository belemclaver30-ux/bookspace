@props(['nombre' => 0, 'compteur' => null])
{{--
    Pastille rouge du nombre d'éléments non lus. Avec `compteur`
    ("notifications" | "messages"), elle se met à jour sans recharger la page
    quand un composant émet l'événement `compteur` (voir resources/js/app.js).
--}}
@if ($nombre > 0 || $compteur)
    <span {{ $attributes->merge(['class' => 'inline-flex h-[18px] min-w-[18px] shrink-0 items-center justify-center rounded-full bg-red-500 px-1 text-[11px] font-bold leading-none text-white']) }}
          @if ($compteur) data-compteur="{{ $compteur }}" @endif
          @if ($nombre <= 0) hidden @endif
          aria-label="{{ $nombre }} non lu{{ $nombre > 1 ? 's' : '' }}">{{ $nombre > 99 ? '99+' : $nombre }}</span>
@endif
