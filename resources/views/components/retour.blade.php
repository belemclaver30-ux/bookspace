@props(['href' => null])
{{--
    Bouton retour : ramène à la page précédente de l'application (historique du
    navigateur). `href` n'est qu'une destination de secours, utilisée quand on
    est arrivé directement sur la page (lien partagé, nouvel onglet, retour de
    la page de paiement…) : on ne sort alors jamais de l'application.
--}}
@php
    $secours = $href ?? (auth()->check() ? auth()->user()->accueil() : '/');
@endphp
<a href="{{ $secours }}" onclick="return retourIntelligent(event)" data-connecte="{{ auth()->check() ? '1' : '0' }}"
   class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-[10px] bg-white text-brand-dark shadow-carte"
   aria-label="Retour">
    <x-icone nom="retour" taille="22" />
</a>
