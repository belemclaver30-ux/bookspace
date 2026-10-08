// Bouton « Retour » (composant <x-retour>) : revient à la page précédente de
// l'application ; sinon, suit le lien de secours du bouton.

// Pages qu'un utilisateur connecté ne peut pas revoir (elles le redirigent) :
// y revenir le ferait tourner en rond.
const PAGES_INVITE = ['/', '/connexion', '/inscription', '/mot-de-passe-oublie'];

/** @returns {boolean} false si la navigation a été prise en charge (history.back). */
export function retourIntelligent(event) {
    const lien = event.currentTarget;
    let precedente = null;

    try {
        precedente = document.referrer ? new URL(document.referrer) : null;
    } catch {
        precedente = null;
    }

    const memeApplication = precedente !== null
        && precedente.origin === window.location.origin
        && precedente.pathname !== window.location.pathname;
    const pageInutile = lien.dataset.connecte === '1' && PAGES_INVITE.includes(precedente?.pathname);

    if (memeApplication && !pageInutile && window.history.length > 1) {
        event.preventDefault();
        window.history.back();

        return false;
    }

    return true; // destination de secours (href du bouton)
}
