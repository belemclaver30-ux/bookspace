@props(['titre', 'sousTitre' => null])
{{-- En-tête d'une sous-page du profil : retour + titre (bandeau sombre côté client). --}}
@php $client = auth()->user()->estClient(); @endphp
<div @class(['flex items-center gap-3', 'bg-brand-dark px-5 pb-[18px] pt-5' => $client, 'mb-5' => ! $client])>
    <x-retour href="/profil" />
    <div class="min-w-0">
        <h1 @class(['font-display text-xl font-bold', 'text-white' => $client, 'text-brand-dark' => ! $client])>{{ $titre }}</h1>
        @if ($sousTitre)
            <p @class(['text-xs', 'text-white/55' => $client, 'text-slate-500' => ! $client])>{{ $sousTitre }}</p>
        @endif
    </div>
</div>
