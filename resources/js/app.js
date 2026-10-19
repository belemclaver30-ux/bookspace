import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { carteItineraire, carteLocalisation, positionActuelle } from './cartes';
import { retourIntelligent } from './retour';
import { visiteGuidee } from './visite';
import { vueImmersive } from './vue-immersive';
import { synchroniserAvantEnvoi } from './formulaires';

// Position du navigateur, utilisable depuis les vues (« Près de moi »).
window.positionActuelle = positionActuelle;
// Bouton « Retour » : page précédente de l'application (voir retour.js).
window.retourIntelligent = retourIntelligent;

Alpine.data('carteItineraire', carteItineraire);
Alpine.data('carteLocalisation', carteLocalisation);
// Visite guidée du nouvel utilisateur (composant Livewire VisiteGuidee).
Alpine.data('visiteGuidee', visiteGuidee);
// Vue immersive d'un espace : 4 angles ou panorama 360° (<x-vue-immersive>).
Alpine.data('vueImmersive', vueImmersive);

// Pastilles « non lus » de la navigation (<x-pastille compteur="…">) : mises à
// jour sans recharger la page quand un composant Livewire émet `compteur`.
window.addEventListener('compteur', ({ detail }) => {
    document.querySelectorAll(`[data-compteur="${detail.type}"]`).forEach((el) => {
        el.textContent = detail.nombre > 99 ? '99+' : String(detail.nombre);
        el.hidden = detail.nombre <= 0;
        el.setAttribute('aria-label', `${detail.nombre} non lu${detail.nombre > 1 ? 's' : ''}`);
    });
});

// Champs remplis automatiquement sans événement : relus avant chaque envoi (voir formulaires.js).
synchroniserAvantEnvoi();

Livewire.start();
