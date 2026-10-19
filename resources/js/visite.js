// Visite guidée du nouvel utilisateur (composant Livewire VisiteGuidee).
// Chaque étape vise un élément `data-visite="…"` : le premier visible parmi
// `cibles` est mis en lumière (barre latérale sur ordinateur, barre basse ou
// bouton menu sur mobile) ; sans cible visible, la bulle est centrée.

const MARGE = 16; // distance minimale aux bords de l'écran
const HALO = 6; // espace autour de l'élément mis en lumière
const ECART = 14; // distance entre le halo et la bulle

const borner = (valeur, min, max) => Math.max(min, Math.min(valeur, max));

/** Élément affiché à l'écran (ni masqué, ni hors champ comme un tiroir fermé). */
function visible(el) {
    const r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0 && r.right > 0 && r.left < window.innerWidth
        && getComputedStyle(el).visibility !== 'hidden';
}

function trouver(etape) {
    for (const nom of etape.cibles ?? []) {
        const el = [...document.querySelectorAll(`[data-visite="${nom}"]`)].find(visible);
        if (el) return el;
    }
    return null;
}

export function visiteGuidee(etapes) {
    return {
        etapes,
        i: 0,
        actif: false,
        trou: '',
        bulle: '',
        fleche: '',

        init() {
            // Étapes sans élément à montrer (ex. aucun espace dans la liste) : retirées.
            this.etapes = etapes.filter((e) => !e.cibles || trouver(e));

            const replacer = () => this.actif && this.placer();
            window.addEventListener('resize', replacer);
            window.addEventListener('scroll', replacer, { passive: true, capture: true });

            // Laisse la page s'afficher avant d'ouvrir la visite.
            setTimeout(() => {
                this.actif = true;
                this.afficher();
            }, 500);
        },

        get etape() {
            return this.etapes[this.i];
        },

        get derniere() {
            return this.i === this.etapes.length - 1;
        },

        menuOuvert: false,

        async afficher() {
            await this.basculerMenu(Boolean(this.etape.menu));
            const el = trouver(this.etape);
            el?.scrollIntoView({ block: 'center', behavior: 'instant' });
            this.placer();
            this.$nextTick(() => (this.derniere ? this.$refs.fin : this.$refs.suivant)?.focus({ preventScroll: true }));
        },

        /**
         * Sur mobile, la navigation du back-office est dans un tiroir : on
         * l'ouvre le temps des étapes qui présentent ses entrées.
         */
        async basculerMenu(ouvrir) {
            const mobile = [...document.querySelectorAll('[data-visite="menu"]')].some(visible);
            const voulu = ouvrir && mobile;
            if (voulu === this.menuOuvert) return;
            this.menuOuvert = voulu;
            window.dispatchEvent(new CustomEvent('visite-menu', { detail: voulu }));
            await new Promise((r) => setTimeout(r, 260)); // animation du tiroir
        },

        placer() {
            const el = trouver(this.etape);

            // La hauteur de la bulle dépend du texte de l'étape : mesurée après rendu.
            this.$nextTick(() => {
                const bulle = this.$refs.bulle;
                const largeur = bulle.offsetWidth;
                const hauteur = bulle.offsetHeight;

                if (!el) {
                    this.trou = '';
                    this.fleche = '';
                    this.bulle = 'top:50%;left:50%;transform:translate(-50%,-50%)';
                    return;
                }

                const r = el.getBoundingClientRect();
                this.trou = `top:${r.top - HALO}px;left:${r.left - HALO}px;`
                    + `width:${r.width + 2 * HALO}px;height:${r.height + 2 * HALO}px;`
                    + 'box-shadow:0 0 0 9999px rgba(26,10,3,0.75)';

                // Sous l'élément si la place le permet, sinon au-dessus.
                const enDessous = r.bottom + HALO + ECART + hauteur <= window.innerHeight - MARGE
                    || r.top - HALO - ECART - hauteur < MARGE;
                const top = borner(
                    enDessous ? r.bottom + HALO + ECART : r.top - HALO - ECART - hauteur,
                    MARGE,
                    window.innerHeight - hauteur - MARGE,
                );
                const left = borner(r.left + r.width / 2 - largeur / 2, MARGE, window.innerWidth - largeur - MARGE);
                this.bulle = `top:${top}px;left:${left}px`;

                const flecheX = borner(r.left + r.width / 2 - left - 7, 18, largeur - 32);
                this.fleche = `left:${flecheX}px;` + (enDessous ? 'top:-7px' : 'bottom:-7px');
            });
        },

        suivant() {
            if (this.derniere) {
                this.terminer(this.etape.lien?.url);
                return;
            }
            this.i++;
            this.afficher();
        },

        precedent() {
            if (this.i > 0) {
                this.i--;
                this.afficher();
            }
        },

        passer() {
            this.terminer();
        },

        async terminer(url = null) {
            this.actif = false;
            await this.basculerMenu(false);
            await this.$wire.terminer();
            window.scrollTo({ top: 0 });
            if (url) window.location.href = url;
        },

        clavier(e) {
            if (!this.actif) return;
            if (e.key === 'Escape') this.passer();
            else if (e.key === 'ArrowRight') this.suivant();
            else if (e.key === 'ArrowLeft') this.precedent();
            else if (e.key === 'Tab') {
                // Garde le focus dans la bulle.
                const focusables = [...this.$refs.bulle.querySelectorAll('button')].filter((b) => b.offsetParent);
                const [premier, dernier] = [focusables[0], focusables.at(-1)];
                if (e.shiftKey && document.activeElement === premier) {
                    e.preventDefault();
                    dernier.focus();
                } else if (!e.shiftKey && document.activeElement === dernier) {
                    e.preventDefault();
                    premier.focus();
                }
            }
        },
    };
}
