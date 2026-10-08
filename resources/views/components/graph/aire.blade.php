@props(['points' => []])
{{--
    Aire / tendance (évolution dans le temps, une série). SVG rendu côté serveur,
    responsive (viewBox). `points` : [['x' => libellé, 'valeur' => nombre], …].
    Le détail de chaque point est donné au survol (infobulle native).
--}}
@php
    $points = array_values(is_array($points) ? $points : $points->all());
    $n = count($points);
    $W = 320; $H = 140; $gauche = 6; $droite = 6; $haut = 10; $bas = 18;
    $max = max(1, ...array_map(fn ($p) => (float) $p['valeur'], $points ?: [['valeur' => 1]]));
    $x = fn (int $i) => round($gauche + ($n > 1 ? $i / ($n - 1) : 0) * ($W - $gauche - $droite), 2);
    $y = fn (float $v) => round($haut + (1 - $v / $max) * ($H - $haut - $bas), 2);
    $ligne = collect($points)->map(fn ($p, $i) => $x($i) . ',' . $y((float) $p['valeur']))->implode(' ');
    $graduations = $n > 1 ? array_unique([0, (int) ceil(($n - 1) / 2), $n - 1]) : [];
    $pas = $n > 1 ? ($W - $gauche - $droite) / ($n - 1) : 0;
@endphp
@if ($n < 2)
    <x-graph.vide />
@else
    <svg viewBox="0 0 {{ $W }} {{ $H }}" class="block h-auto w-full" role="img" aria-label="Évolution sur la période">
        @foreach ([0.5, 1] as $f)
            <line x1="{{ $gauche }}" x2="{{ $W - $droite }}" y1="{{ $y($max * $f) }}" y2="{{ $y($max * $f) }}" stroke="#EEF2F7" stroke-width="1" />
        @endforeach
        <polygon points="{{ $gauche }},{{ $H - $bas }} {{ $ligne }} {{ $W - $droite }},{{ $H - $bas }}" fill="rgba(252,78,0,0.12)" />
        <polyline points="{{ $ligne }}" fill="none" stroke="#FC4E00" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
        @foreach ($points as $i => $p)
            <g class="group">
                <rect x="{{ round($x($i) - $pas / 2, 2) }}" y="0" width="{{ round($pas, 2) }}" height="{{ $H - $bas }}" fill="transparent" />
                <circle cx="{{ $x($i) }}" cy="{{ $y((float) $p['valeur']) }}" r="3.5" fill="#FC4E00" stroke="#fff" stroke-width="2" class="opacity-0 group-hover:opacity-100" />
                <title>{{ $p['x'] }} · {{ fcfa($p['valeur']) }}</title>
            </g>
        @endforeach
        @foreach ($graduations as $i)
            <text x="{{ $x($i) }}" y="{{ $H - 5 }}" font-size="9.5" fill="#94A3B8"
                  text-anchor="{{ $i === 0 ? 'start' : ($i === $n - 1 ? 'end' : 'middle') }}">{{ $points[$i]['x'] }}</text>
        @endforeach
    </svg>
@endif
