@props(['titre', 'miseAJour'])
{{-- Page de texte légal (conditions, confidentialité) : accessible sans connexion. --}}
<x-layouts.ecran :title="$titre" fond="bg-white">
    <div class="sticky top-0 z-10 flex items-center gap-3 border-b border-slate-100 bg-white px-5 pb-4 pt-5">
        <x-retour :href="auth()->check() ? '/profil' : '/inscription'" />
        <h1 class="font-display text-[17px] font-bold text-brand-dark">{{ $titre }}</h1>
    </div>

    <article class="px-5 pb-10 pt-5 text-[13.5px] leading-relaxed text-slate-600
                    [&_a]:font-semibold [&_a]:text-brand [&_a]:underline
                    [&_h2]:mb-1.5 [&_h2]:mt-6 [&_h2]:text-sm [&_h2]:font-bold [&_h2]:text-brand-dark
                    [&_li]:mb-1 [&_p]:mb-2.5 [&_ul]:mb-2.5 [&_ul]:list-disc [&_ul]:pl-5">
        <p class="text-xs text-slate-400">Dernière mise à jour : {{ $miseAJour }}</p>
        {{ $slot }}
    </article>
</x-layouts.ecran>
