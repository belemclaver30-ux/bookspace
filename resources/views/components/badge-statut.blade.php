@props(['statut'])
@php
    $styles = [
        'confirmee' => ['bg-green-100 text-green-600', 'Confirmée'],
        'en_attente' => ['bg-[#FEF0D6] text-attente', 'En attente'],
        'annulee' => ['bg-red-100 text-red-600', 'Annulée'],
        'terminee' => ['bg-brand-100 text-brand-dark', 'Terminée'],
        'valide' => ['bg-green-100 text-green-600', 'Validé'],
        'rejete' => ['bg-red-100 text-red-600', 'Rejeté'],
        'suspendu' => ['bg-[#FEF0D6] text-attente', 'Suspendu'],
    ];
    [$classes, $libelle] = $styles[$statut] ?? $styles['en_attente'];
@endphp
<span class="shrink-0 rounded-full px-[9px] py-[3px] text-[11px] font-semibold {{ $classes }}">{{ $libelle }}</span>
