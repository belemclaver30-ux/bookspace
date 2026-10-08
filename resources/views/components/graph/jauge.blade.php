@props(['pct' => 0, 'libelle' => ''])
{{-- Jauge en demi-cercle (une valeur en %). --}}
@php
    $v = max(0, min(100, (float) $pct));
    $R = 46; $C = 56;
    $circonference = M_PI * $R;
    $arc = "M " . ($C - $R) . " {$C} A {$R} {$R} 0 0 1 " . ($C + $R) . " {$C}";
@endphp
<div class="text-center">
    <svg viewBox="0 0 112 70" class="mx-auto block h-[90px] w-full max-w-[180px]" role="img" aria-label="{{ $v }} % {{ $libelle }}">
        <path d="{{ $arc }}" fill="none" stroke="#EEF2F7" stroke-width="12" stroke-linecap="round" />
        @if ($v > 0)
            <path d="{{ $arc }}" fill="none" stroke="#FC4E00" stroke-width="12"
                  stroke-dasharray="{{ round($v / 100 * $circonference, 2) }} {{ round($circonference, 2) }}" />
        @endif
        <text x="{{ $C }}" y="{{ $C - 6 }}" text-anchor="middle" font-size="20" font-weight="800" fill="#334155">{{ rtrim(rtrim(number_format($v, 1, ',', ''), '0'), ',') }}%</text>
    </svg>
    <div class="-mt-1.5 text-xs font-semibold text-slate-400">{{ $libelle }}</div>
</div>
