@props(['titre', 'sousTitre' => null])
<div class="px-6 py-12 text-center text-slate-400">
    <div class="mb-1 text-[15px] font-semibold text-slate-600">{{ $titre }}</div>
    @if ($sousTitre)
        <div class="text-[13px]">{{ $sousTitre }}</div>
    @endif
</div>
