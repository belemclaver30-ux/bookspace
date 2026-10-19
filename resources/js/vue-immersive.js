// Visionneuse de la vue immersive d'un espace (composant <x-vue-immersive>).
//
// Mode `angles` : les 4 photos (face, droite, arrière, gauche) forment une
// bande qui boucle. On glisse pour « tourner » ; la boussole suit le geste.
// La bande contient une copie de la dernière photo au début et de la première
// à la fin : en arrivant sur une copie, on se replace sans animation sur
// l'originale, ce qui donne une rotation continue.
//
// Mode `panorama` : Photo Sphere Viewer, importé seulement à l'ouverture
// (three.js est lourd : rien n'est téléchargé tant qu'on n'ouvre pas la vue).

const SEUIL = 0.18; // part de la largeur à glisser pour changer d'angle

export function vueImmersive(donnees) {
    const angles = donnees.angles ?? [];

    return {
        mode: donnees.type,
        angles,
        bande: angles.length ? [angles.at(-1), ...angles, angles[0]] : [],
        ouvert: false,
        position: 1, // index dans la bande (1 = première photo)
        decalage: 0, // déplacement du doigt en cours (px)
        glisse: false,
        anime: true,
        aTourne: false,
        departX: 0,
        largeur: 1,
        chargement: false,
        erreur: '',
        sphere: null,

        get actuel() {
            return (this.position - 1 + this.angles.length) % this.angles.length;
        },

        /** Direction regardée en degrés (suit le doigt pendant le glissement). */
        get cap() {
            return (this.position - 1) * 90 - (this.decalage / this.largeur) * 90;
        },

        get styleBande() {
            const transition = this.anime && !this.glisse ? 'transform 320ms cubic-bezier(.2,.8,.2,1)' : 'none';
            return `transform: translateX(calc(${-this.position * 100}% + ${this.decalage}px)); transition: ${transition}`;
        },

        ouvrir() {
            this.ouvert = true;
            document.documentElement.style.overflow = 'hidden';
            this.$nextTick(() => {
                this.$refs.fermer?.focus();
                if (this.mode === 'panorama') this.chargerSphere();
            });
        },

        fermer() {
            this.ouvert = false;
            document.documentElement.style.overflow = '';
            this.sphere?.destroy();
            this.sphere = null;
        },

        // ─── Angles ─────────────────────────────────────────────────────────

        tourner(sens) {
            this.aTourne = true;
            this.anime = true;
            this.position += sens;
        },

        aller(n) {
            this.aTourne = true;
            this.anime = true;
            this.position = n + 1;
        },

        /** Arrivé sur une copie en bout de bande : retour sans animation sur l'originale. */
        finTransition() {
            const n = this.angles.length;
            if (this.position === 0 || this.position === n + 1) {
                this.anime = false;
                this.position = this.position === 0 ? n : 1;
                requestAnimationFrame(() => requestAnimationFrame(() => (this.anime = true)));
            }
        },

        debutGlisser(e) {
            if (e.button !== undefined && e.button !== 0) return;
            this.glisse = true;
            this.departX = e.clientX;
            this.largeur = this.$refs.scene.offsetWidth || 1;
            this.$refs.scene.setPointerCapture?.(e.pointerId);
        },

        glisser(e) {
            if (this.glisse) this.decalage = e.clientX - this.departX;
        },

        finGlisser() {
            if (!this.glisse) return;
            const part = this.decalage / this.largeur;
            this.glisse = false;
            this.decalage = 0;
            if (Math.abs(part) > SEUIL) this.tourner(part < 0 ? 1 : -1);
        },

        // ─── Panorama ───────────────────────────────────────────────────────

        async chargerSphere() {
            if (this.sphere) return;
            this.chargement = true;
            this.erreur = '';

            try {
                const [{ Viewer }] = await Promise.all([
                    import('@photo-sphere-viewer/core'),
                    import('@photo-sphere-viewer/core/index.css'),
                ]);

                this.sphere = new Viewer({
                    container: this.$refs.sphere,
                    panorama: donnees.url,
                    panoData: donneesPanorama(donnees.largeur, donnees.hauteur),
                    navbar: ['zoom', 'fullscreen'],
                    loadingTxt: '',
                    defaultZoomLvl: 30,
                    touchmoveTwoFingers: false,
                    mousewheelCtrlKey: false,
                    lang: { zoomOut: 'Dézoomer', zoomIn: 'Zoomer', fullscreen: 'Plein écran' },
                });
                this.sphere.addEventListener('ready', () => (this.chargement = false), { once: true });
            } catch {
                this.chargement = false;
                this.erreur = 'La vue 360° n’a pas pu être chargée. Vérifiez votre connexion.';
            }
        },

        clavier(e) {
            if (!this.ouvert) return;
            if (e.key === 'Escape') this.fermer();
            else if (this.mode === 'angles' && e.key === 'ArrowRight') this.tourner(1);
            else if (this.mode === 'angles' && e.key === 'ArrowLeft') this.tourner(-1);
        },
    };
}

/**
 * Photo 360° (2:1) : sphère complète. Panorama de téléphone (bande plus
 * large) : on considère qu'il couvre un tour complet et on le centre
 * verticalement sur la sphère.
 */
function donneesPanorama(largeur, hauteur) {
    if (!largeur || !hauteur || Math.abs(largeur / hauteur - 2) < 0.1) return undefined;

    return {
        fullWidth: largeur,
        fullHeight: Math.round(largeur / 2),
        croppedWidth: largeur,
        croppedHeight: hauteur,
        croppedX: 0,
        croppedY: Math.round((largeur / 2 - hauteur) / 2),
    };
}
