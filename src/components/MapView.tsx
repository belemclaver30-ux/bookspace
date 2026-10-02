import { useEffect, useRef } from "react";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

export interface LatLng {
  lat: number;
  lng: number;
}

interface Props {
  /** Centre initial. Par défaut : Ouagadougou. */
  center?: LatLng;
  zoom?: number;
  height?: number | string;
  /** Marqueur principal (position d'un espace). */
  marker?: LatLng | null;
  /** Rend le marqueur déplaçable ; appelé à la fin du glisser. */
  onMarkerMove?: (p: LatLng) => void;
  /** Clic sur la carte -> place / déplace le marqueur. */
  onPick?: (p: LatLng) => void;
  /** Marqueurs secondaires (liste d'espaces sur la page d'accueil). */
  points?: { id: number | string; lat: number; lng: number; label?: string }[];
  /** Position de l'utilisateur (point bleu). */
  user?: LatLng | null;
  /** Tracé d'itinéraire [[lat, lng], ...]. */
  route?: [number, number][];
  interactive?: boolean;
}

const OUAGA: LatLng = { lat: 12.3714, lng: -1.5197 };

const pin = (couleur: string) =>
  L.divIcon({
    className: "",
    html: `<svg width="30" height="40" viewBox="0 0 30 40" xmlns="http://www.w3.org/2000/svg">
      <path d="M15 0C6.7 0 0 6.7 0 15c0 10.5 15 25 15 25s15-14.5 15-25C30 6.7 23.3 0 15 0z" fill="${couleur}"/>
      <circle cx="15" cy="15" r="6" fill="#fff"/>
    </svg>`,
    iconSize: [30, 40],
    iconAnchor: [15, 40],
    popupAnchor: [0, -36],
  });

export function MapView({
  center, zoom = 13, height = 220, marker, onMarkerMove, onPick, points, user, route, interactive = true,
}: Props) {
  const el = useRef<HTMLDivElement>(null);
  const map = useRef<L.Map | null>(null);
  const layers = useRef<L.LayerGroup | null>(null);

  // Init / destroy
  useEffect(() => {
    if (!el.current || map.current) return;
    const m = L.map(el.current, {
      center: [center?.lat ?? OUAGA.lat, center?.lng ?? OUAGA.lng],
      zoom,
      zoomControl: interactive,
      dragging: interactive,
      scrollWheelZoom: interactive,
      doubleClickZoom: interactive,
      attributionControl: true,
    });
    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: "© OpenStreetMap",
    }).addTo(m);
    layers.current = L.layerGroup().addTo(m);
    map.current = m;

    return () => {
      m.remove();
      map.current = null;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Clic pour placer un marqueur
  useEffect(() => {
    const m = map.current;
    if (!m) return;
    const handler = (e: L.LeafletMouseEvent) => onPick?.({ lat: +e.latlng.lat.toFixed(6), lng: +e.latlng.lng.toFixed(6) });
    if (onPick) m.on("click", handler);
    return () => {
      m.off("click", handler);
    };
  }, [onPick]);

  // (Re)dessine les couches à chaque changement de données
  useEffect(() => {
    const m = map.current;
    const g = layers.current;
    if (!m || !g) return;
    g.clearLayers();

    const bounds: L.LatLngExpression[] = [];

    if (marker) {
      const mk = L.marker([marker.lat, marker.lng], {
        icon: pin("#FC4E00"),
        draggable: Boolean(onMarkerMove),
      }).addTo(g);
      if (onMarkerMove) {
        mk.on("dragend", () => {
          const p = mk.getLatLng();
          onMarkerMove({ lat: +p.lat.toFixed(6), lng: +p.lng.toFixed(6) });
        });
      }
      bounds.push([marker.lat, marker.lng]);
    }

    (points ?? []).forEach((p) => {
      const mk = L.marker([p.lat, p.lng], { icon: pin("#BD3100") }).addTo(g);
      if (p.label) mk.bindPopup(p.label);
      bounds.push([p.lat, p.lng]);
    });

    if (user) {
      L.circleMarker([user.lat, user.lng], {
        radius: 7, color: "#fff", weight: 2, fillColor: "#2563EB", fillOpacity: 1,
      }).addTo(g).bindPopup("Vous êtes ici");
      bounds.push([user.lat, user.lng]);
    }

    if (route && route.length > 1) {
      L.polyline(route, { color: "#FC4E00", weight: 5, opacity: 0.85 }).addTo(g);
      route.forEach((p) => bounds.push(p));
    }

    if (bounds.length > 1) {
      m.fitBounds(L.latLngBounds(bounds).pad(0.25), { maxZoom: 16 });
    } else if (bounds.length === 1) {
      m.setView(bounds[0], zoom);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [marker?.lat, marker?.lng, JSON.stringify(points), user?.lat, user?.lng, JSON.stringify(route)]);

  return (
    <div
      ref={el}
      style={{ width: "100%", height, borderRadius: 12, overflow: "hidden", zIndex: 0, background: "#E8EEF3" }}
    />
  );
}

export { OUAGA };
