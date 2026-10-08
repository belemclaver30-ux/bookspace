@props(['titre', 'description' => null, 'enregistre' => false, 'danger' => false])
{{-- Bloc d'une sous-page du profil : titre, description, contenu (formulaire). --}}
<section {{ $attributes->merge(['class' => 'mb-4 rounded-2xl bg-white p-4 shadow-carte']) }}>
    <h2 @class(['text-sm font-bold', 'text-red-700' => $danger, 'text-brand-dark' => ! $danger])>
        {{ $titre }}
        @if ($enregistre)
            <span class="ml-1 text-xs font-semibold text-green-600" role="status">✓ Enregistré</span>
        @endif
    </h2>
    @if ($description)
        <p class="mb-3 mt-0.5 text-xs leading-normal text-slate-500">{{ $description }}</p>
    @else
        <div class="mb-3"></div>
    @endif
    {{ $slot }}
</section>
