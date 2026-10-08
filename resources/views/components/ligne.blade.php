@props(['libelle', 'valeur' => null])
{{-- Ligne « libellé … valeur » des récapitulatifs. --}}
<div {{ $attributes->merge(['class' => 'mb-2.5 flex justify-between gap-3']) }}>
    <span class="text-[13px] text-slate-500">{{ $libelle }}</span>
    <span class="text-right text-[13px] font-semibold text-brand-dark">{{ $valeur ?? $slot }}</span>
</div>
