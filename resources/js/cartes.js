// Cartes Leaflet (OpenStreetMap). Leaflet est chargé à la demande : seules les
// pages qui affichent une carte le téléchargent (TDR §9 : connexion limitée).

const OUAGA = { lat: 12.3714, lng: -1.5197 };

let leaflet = null;

async function chargerLeaflet() {
    if (!leaflet) {
        const [module] = await Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')]);
        leaflet = module.default;
    }
    return leaflet;
}

function epingle(L, couleur) {
    return L.divIcon({
        className: '',
        html: `<svg width="30" height="40" viewBox="0 0 30 40" xmlns="http://www.w3.org/2000/svg">
      <path d="M15 0C6.7 0 0 6.7 0 15c0 10.5 15 25 15 25s15-14.5 15-25C30 6.7 23.3 0 15 0z" fill="${couleur}"/>
      <circle cx="15" cy="15" r="6" fill="#fff"/>
    </svg>`,
        iconSize: [30, 40],
        iconAnchor: [15, 40],
        popupAnchor: [0, -36],
    });
}

async function creerCarte(element, centre, zoom) {
    const L = await chargerLeaflet();
    const carte = L.map(element, { center: [centre.lat, centre.lng], zoom });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap',
    }).addTo(carte);

    return { L, carte, couches: L.layerGroup().addTo(carte) };
}

const arrondi = (n) => +Number(n).toFixed(6);

/** Géolocalisation du navigateur, en promesse. */
export function positionActuelle() {
    return new Promise((resolve, reject) => {
        if (!('geolocation' in navigator)) {
            reject(new Error("La géolocalisation n'est pas disponible sur cet appareil."));
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => resolve({ lat: arrondi(pos.coords.latitude), lng: arrondi(pos.coords.longitude) }),
            (err) => {
                reject(new Error(
                    err.code === err.PERMISSION_DENIED
                        ? 'Accès à la position refusé. Autorisez la localisation dans votre navigateur.'
                        : "Impossible d'obtenir votre position.",
                ));
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 },
        );
    });
}

/**
 * Fiche espace : carte de l'espace + itinéraire depuis la position du client.
 * Le calcul d'itinéraire passe par le composant Livewire (`$wire.itineraire`).
 */
export function carteItineraire(dest) {
    let ctx = null;

    return {
        moi: null,
        route: null,
        occupe: false,
        erreur: '',

        async init() {
            ctx = await creerCarte(this.$refs.carte, dest, 13);
            this.dessiner();
        },

        dessiner() {
            const { L, carte, couches } = ctx;
            couches.clearLayers();
            const limites = [[dest.lat, dest.lng]];

            L.marker([dest.lat, dest.lng], { icon: epingle(L, '#FC4E00') }).addTo(couches);

            if (this.moi) {
                L.circleMarker([this.moi.lat, this.moi.lng], {
                    radius: 7, color: '#fff', weight: 2, fillColor: '#2563EB', fillOpacity: 1,
                }).addTo(couches).bindPopup('Vous êtes ici');
                limites.push([this.moi.lat, this.moi.lng]);
            }

            if (this.route?.coordonnees?.length > 1) {
                L.polyline(this.route.coordonnees, { color: '#FC4E00', weight: 5, opacity: 0.85 }).addTo(couches);
                this.route.coordonnees.forEach((p) => limites.push(p));
            }

            if (limites.length > 1) {
                carte.fitBounds(L.latLngBounds(limites).pad(0.25), { maxZoom: 16 });
            } else {
                carte.setView(limites[0], 13);
            }
        },

        async calculer() {
            this.occupe = true;
            this.erreur = '';
            try {
                this.moi = await positionActuelle();
                const reponse = await this.$wire.itineraire(this.moi.lat, this.moi.lng);
                if (reponse.erreur) {
                    this.erreur = reponse.erreur;
                } else {
                    this.route = reponse;
                }
                if (ctx) this.dessiner();
            } catch (e) {
                this.erreur = e.message;
            } finally {
                this.occupe = false;
            }
        },

        /** Lien d'itinéraire vers l'application de navigation du téléphone. */
        get lienNavigation() {
            const params = new URLSearchParams({
                api: '1', destination: `${dest.lat},${dest.lng}`, travelmode: 'driving',
            });
            if (this.moi) params.set('origin', `${this.moi.lat},${this.moi.lng}`);
            return `https://www.google.com/maps/dir/?${params.toString()}`;
        },
    };
}

/**
 * Gestion d'un espace (gérant) : clic ou glisser du marqueur pour fixer la
 * position. Les coordonnées sont écrites dans le composant Livewire
 * (`latitude` / `longitude`) sans requête, puis envoyées à l'enregistrement.
 */
export function carteLocalisation() {
    let ctx = null;

    return {
        point: null,
        occupe: false,
        erreur: '',

        async init() {
            // Position initiale lue dans le composant Livewire : l'attribut
            // x-data reste ainsi constant et n'est pas réinitialisé au re-rendu.
            const { latitude, longitude } = this.$wire;
            this.point = latitude !== null && longitude !== null ? { lat: latitude, lng: longitude } : null;

            ctx = await creerCarte(this.$refs.carte, this.point ?? OUAGA, this.point ? 15 : 12);
            ctx.carte.on('click', (e) => this.placer({ lat: arrondi(e.latlng.lat), lng: arrondi(e.latlng.lng) }));
            this.dessiner();
        },

        dessiner() {
            if (!ctx) return;
            const { L, carte, couches } = ctx;
            couches.clearLayers();
            if (!this.point) return;

            const marqueur = L.marker([this.point.lat, this.point.lng], {
                icon: epingle(L, '#FC4E00'), draggable: true,
            }).addTo(couches);
            marqueur.on('dragend', () => {
                const p = marqueur.getLatLng();
                this.placer({ lat: arrondi(p.lat), lng: arrondi(p.lng) }, false);
            });
            carte.setView([this.point.lat, this.point.lng], Math.max(carte.getZoom(), 15));
        },

        /** Fixe le point (depuis la carte) et le reporte dans le composant Livewire. */
        placer(p, redessiner = true) {
            this.point = p;
            this.$wire.set('latitude', p.lat, false);
            this.$wire.set('longitude', p.lng, false);
            if (redessiner) this.dessiner();
        },

        /** Point choisi côté serveur (résultat de recherche d'adresse). */
        deplacer(p) {
            this.point = p;
            this.dessiner();
        },

        async maPosition() {
            this.occupe = true;
            this.erreur = '';
            try {
                const pos = await positionActuelle();
                this.point = pos;
                this.dessiner();
                await this.$wire.utiliserPosition(pos.lat, pos.lng);
            } catch (e) {
                this.erreur = e.message;
            } finally {
                this.occupe = false;
            }
        },
    };
}
