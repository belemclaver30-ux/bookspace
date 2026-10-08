@props(['titre' => null])
<div {{ $attributes->merge(['class' => 'rounded-[14px] bg-white p-5 shadow-carte']) }}>
    @if ($titre)
        <div class="titre-section mb-3.5">{{ $titre }}</div>
    @endif
    {{ $slot }}
</div>
