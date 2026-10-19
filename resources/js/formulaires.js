// Champs remplis automatiquement (gestionnaire de mots de passe, saisie
// automatique Android/Google) : certains services posent la valeur dans le
// champ sans émettre d'événement `input`. Livewire (`wire:model`) croit alors
// le champ vide et le serveur répond « Le champ … est obligatoire » alors
// qu'il est rempli à l'écran.
//
// Juste avant l'envoi d'un formulaire Livewire, on renvoie donc un événement
// pour chaque champ : `wire:model` relit la valeur réellement affichée. Écouté
// en phase de capture, ce code passe avant le `wire:submit` du formulaire.

const SANS_SAISIE = ['file', 'submit', 'button', 'reset', 'image', 'hidden'];

export function synchroniserAvantEnvoi() {
    document.addEventListener(
        'submit',
        (e) => {
            const formulaire = e.target;
            if (!(formulaire instanceof HTMLFormElement) || !formulaire.hasAttribute('wire:submit')) return;

            formulaire.querySelectorAll('input, textarea, select').forEach((champ) => {
                const lie = [...champ.attributes].some((a) => a.name.startsWith('wire:model'));
                if (!lie || SANS_SAISIE.includes(champ.type)) return;

                const coche = champ.type === 'checkbox' || champ.type === 'radio' || champ.tagName === 'SELECT';
                champ.dispatchEvent(new Event(coche ? 'change' : 'input', { bubbles: true }));
            });
        },
        true,
    );
}
