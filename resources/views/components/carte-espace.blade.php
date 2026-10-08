@props(['espace'])
{{-- Vignette d'un espace dans la liste d'accueil. --}}
@php
    $image = $espace->photos->first()?->url ?? \App\Models\Espace::PHOTO_PAR_DEFAUT;
    $musique = $espace->type === 'salle_repetition';
@endphp
<a href="/espaces/{{ $espace->id }}" {{ $attributes->merge(['class' => 'mb-3.5 block overflow-hidden rounded-2xl bg-white shadow-carte-lg']) }}>
    <div class="relative h-40">
        <img src="{{ $image }}" alt="{{ $espace->nom }}" loading="lazy" class="h-full w-full object-cover">
        <span @class([
            'absolute left-2.5 top-2.5 flex items-center gap-1 rounded-full px-[9px] py-1 text-[11px] font-semibold text-white',
            'bg-brand' => $musique,
            'bg-brand-dark' => ! $musique,
        ])>
            <x-icone :nom="$musique ? 'musique' : 'football'" taille="16" />
            {{ $musique ? 'Salle de répét.' : 'Terrain football' }}
        </span>
    </div>
    <div class="flex items-start justify-between gap-3 px-3.5 py-3">
        <div class="min-w-0">
            <div class="mb-[3px] truncate text-[15px] font-bold text-brand-dark">{{ $espace->nom }}</div>
            <div class="flex items-center gap-1 text-xs text-slate-500">
                <x-icone nom="lieu" taille="13" /><span class="truncate">{{ $espace->ville }}</span>
            </div>
            @isset($espace->distance_km)
                <div class="mt-[3px] text-[11px] font-bold text-brand">à {{ distance_lisible($espace->distance_km) }}</div>
            @endisset
        </div>
        <div class="shrink-0 text-right">
            <div class="text-[15px] font-bold text-brand">
                {{ nombre($espace->tarif_horaire) }}
                <span class="text-[11px] font-medium text-slate-400">FCFA/h</span>
            </div>
            <div class="mt-0.5 flex items-center justify-end gap-[3px]">
                <x-icone nom="etoile" taille="13" plein class="text-gold" />
                <span class="text-xs font-semibold text-gray-700">{{ number_format((float) $espace->note_moyenne, 1, ',', '') }}</span>
                <span class="text-[11px] text-slate-400">({{ $espace->nb_avis }})</span>
            </div>
        </div>
    </div>
</a>
