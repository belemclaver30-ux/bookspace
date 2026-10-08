@props(['donnees' => []])
{{-- Barres horizontales (magnitude). `donnees` : [['libelle' => …, 'valeur' => nombre], …]. --}}
@php
    $donnees = array_values(is_array($donnees) ? $donnees : $donnees->all());
    $max = max(1, ...array_map(fn ($d) => (float) $d['valeur'], $donnees ?: [['valeur' => 1]]));
@endphp
@if (count($donnees) === 0)
    <x-graph.vide />
@else
    <div class="flex flex-col gap-3">
        @foreach ($donnees as $d)
            <div>
                <div class="mb-1 flex justify-between gap-3 text-[11px] font-semibold">
                    <span class="truncate text-slate-700">{{ $d['libelle'] ?? '—' }}</span>
                    <span class="shrink-0 text-slate-400">{{ fcfa($d['valeur']) }}</span>
                </div>
                <div class="h-[7px] rounded-full bg-[#EEF2F7]">
                    <div class="h-[7px] rounded-full bg-brand" style="width: {{ max(1, round($d['valeur'] / $max * 100, 1)) }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
@endif
