@props(['nom', 'taille' => 18, 'plein' => false])
@php
    // Jeu d'icônes SVG BookSpace — la couleur suit `currentColor`.
    $traces = [
        'recherche' => '<circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" />',
        'accueil' => '<path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z" /><path d="M9 21V12h6v9" />',
        'calendrier' => '<rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" />',
        'utilisateur' => '<circle cx="12" cy="8" r="4" /><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" />',
        'etoile' => '<polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" />',
        'lieu' => '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" /><circle cx="12" cy="9" r="2.5" />',
        'retour' => '<path d="M19 12H5m0 0 7-7m-7 7 7 7" />',
        'coche' => '<path d="M20 6 9 17l-5-5" />',
        'chevron' => '<path d="m9 18 6-6-6-6" />',
        'cloche' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" /><path d="M13.73 21a2 2 0 0 1-3.46 0" />',
        'musique' => '<path d="M9 18V5l12-2v13" /><circle cx="6" cy="18" r="3" /><circle cx="18" cy="16" r="3" />',
        'football' => '<circle cx="12" cy="12" r="10" /><path d="M12 2v4m0 12v4M2 12h4m12 0h4m-3.17-6.83-2.83 2.83M7.17 7.17 4.34 4.34M20.66 19.66l-2.83-2.83M7.17 16.83l-2.83 2.83" />',
        'equipement' => '<path d="m9 12 2 2 4-4" /><circle cx="12" cy="12" r="9" />',
        'reglages' => '<circle cx="12" cy="12" r="3" /><path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42m12.72-12.72 1.42-1.42" />',
        'tableau' => '<rect x="3" y="3" width="7" height="9" rx="1" /><rect x="14" y="3" width="7" height="5" rx="1" /><rect x="14" y="12" width="7" height="9" rx="1" /><rect x="3" y="16" width="7" height="5" rx="1" />',
        'batiment' => '<path d="M3 21h18M6 21V7l6-4 6 4v14M10 9h.01M14 9h.01M10 13h.01M14 13h.01M10 17h.01M14 17h.01" />',
        'valider' => '<path d="m9 12 2 2 4-4" /><circle cx="12" cy="12" r="9" />',
        'alerte' => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" /><path d="M12 9v4M12 17h.01" />',
        'curseurs' => '<line x1="4" y1="21" x2="4" y2="14" /><line x1="4" y1="10" x2="4" y2="3" /><line x1="12" y1="21" x2="12" y2="12" /><line x1="12" y1="8" x2="12" y2="3" /><line x1="20" y1="21" x2="20" y2="16" /><line x1="20" y1="12" x2="20" y2="3" /><line x1="1" y1="14" x2="7" y2="14" /><line x1="9" y1="8" x2="15" y2="8" /><line x1="17" y1="16" x2="23" y2="16" />',
        'aide' => '<circle cx="12" cy="12" r="9" /><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.7M12 17h.01" />',
        'document' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" /><path d="M14 3v5h5M9 13h6M9 17h6" />',
        'bouclier' => '<path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6z" /><path d="m9 12 2 2 4-4" />',
        'langue' => '<circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" />',
        'drapeau' => '<path d="M5 21V4M5 4h11l-2 4 2 4H5" />',
        'message' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z" />',
        'telephone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z" />',
        'enveloppe' => '<rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" />',
        'coeur' => '<path d="M12 20s-7-4.4-9-9.2C1.6 7.2 4 4 7.2 4c2 0 3.4 1.1 4.8 2.8C13.4 5.1 14.8 4 16.8 4 20 4 22.4 7.2 21 10.8 19 15.6 12 20 12 20z" />',
        'cle' => '<circle cx="8" cy="15" r="4" /><path d="m10.8 12.2 9.2-9.2M17 6l2.5 2.5M14.5 8.5 17 11" />',
        'revenus' => '<rect x="2" y="6" width="20" height="13" rx="2" /><circle cx="12" cy="12.5" r="2.5" /><path d="M6 10v.01M18 15v.01" />',
        'envoyer' => '<path d="M22 2 11 13M22 2l-7 20-4-9-9-4z" />',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18" />',
    ];
@endphp
<svg {{ $attributes->merge(['class' => 'shrink-0']) }} width="{{ $taille }}" height="{{ $taille }}" viewBox="0 0 24 24"
     fill="{{ $plein ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.9"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $traces[$nom] ?? '' !!}</svg>
