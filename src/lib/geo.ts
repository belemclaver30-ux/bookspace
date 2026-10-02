export interface Coord {
  lat: number;
  lng: number;
}

/** Géolocalisation du navigateur, en promesse. */
export function positionActuelle(options?: PositionOptions): Promise<Coord> {
  return new Promise((resolve, reject) => {
    if (!("geolocation" in navigator)) {
      reject(new Error("La géolocalisation n'est pas disponible sur cet appareil."));
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => resolve({ lat: +pos.coords.latitude.toFixed(6), lng: +pos.coords.longitude.toFixed(6) }),
      (err) => {
        const msg =
          err.code === err.PERMISSION_DENIED
            ? "Accès à la position refusé. Autorisez la localisation dans votre navigateur."
            : "Impossible d'obtenir votre position.";
        reject(new Error(msg));
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000, ...options },
    );
  });
}

/** Lien d'itinéraire vers l'application de navigation du téléphone. */
export function lienNavigation(dest: Coord, origine?: Coord | null): string {
  const d = `${dest.lat},${dest.lng}`;
  const params = new URLSearchParams({ api: "1", destination: d, travelmode: "driving" });
  if (origine) params.set("origin", `${origine.lat},${origine.lng}`);
  return `https://www.google.com/maps/dir/?${params.toString()}`;
}

export function formatDistance(km: number): string {
  return km < 1 ? `${Math.round(km * 1000)} m` : `${km.toFixed(km < 10 ? 1 : 0)} km`;
}

export function formatDuree(min: number): string {
  if (min < 60) return `${min} min`;
  const h = Math.floor(min / 60);
  const m = min % 60;
  return m ? `${h} h ${m}` : `${h} h`;
}
