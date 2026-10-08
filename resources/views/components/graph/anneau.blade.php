@props(['segments' => []])
{{--
    Anneau (répartition en parts d'un tout).
    `segments` : [['libelle' => …, 'valeur' => entier, 'couleur' => '#hex'], …].
--}}
@php
    $total = array_sum(array_column($segments, 'valeur'));
    $R = 50; $C = 60; $epaisseur = 16;
    $visibles = array_values(array_filter($segments, fn ($s) => $s['valeur'] > 0));
    $angle = -M_PI / 2;
    $point = fn (float $a) => round($C + $R * cos($a), 3) . ' ' . round($C + $R * sin($a), 3);
@endphp
@if ($total == 0)
    <x-graph.vide />
@else
    <div class="flex flex-wrap items-center gap-5">
        <svg viewBox="0 0 120 120" width="120" height="120" class="shrink-0" role="img" aria-label="Répartition">
            <circle cx="{{ $C }}" cy="{{ $C }}" r="{{ $R }}" fill="none" stroke="#EEF2F7" stroke-width="{{ $epaisseur }}" />
            @foreach ($visibles as $s)
                @php
                    $balayage = $s['valeur'] / $total * M_PI * 2;
                    // Petit espace entre parts pour les séparer visuellement.
                    $ecart = count($visibles) > 1 ? 0.03 : 0;
                    // Une part de 100 % ne peut pas être tracée en un seul arc.
                    $fin = min($angle + $balayage - $ecart / 2, $angle + M_PI * 2 - 0.001);
                    $debut = $angle + $ecart / 2;
                    $grand = ($fin - $debut) > M_PI ? 1 : 0;
                    $angle += $balayage;
                @endphp
                <path d="M {{ $point($debut) }} A {{ $R }} {{ $R }} 0 {{ $grand }} 1 {{ $point($fin) }}"
                      fill="none" stroke="{{ $s['couleur'] }}" stroke-width="{{ $epaisseur }}">
                    <title>{{ $s['libelle'] }} : {{ $s['valeur'] }}</title>
                </path>
            @endforeach
            <text x="{{ $C }}" y="{{ $C - 2 }}" text-anchor="middle" font-size="20" font-weight="800" fill="#334155">{{ $total }}</text>
            <text x="{{ $C }}" y="{{ $C + 13 }}" text-anchor="middle" font-size="9" fill="#94A3B8">total</text>
        </svg>
        <div class="flex min-w-[120px] flex-col gap-1.5">
            @foreach ($segments as $s)
                <div class="flex items-center gap-2 text-xs">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-[3px]" style="background: {{ $s['couleur'] }}"></span>
                    <span class="flex-1 font-semibold text-slate-700">{{ $s['libelle'] }}</span>
                    <span class="font-semibold text-slate-400">{{ $s['valeur'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif
