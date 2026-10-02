import { useState } from "react";
import { api, ApiError } from "../lib/api";
import { MapView } from "./MapView";
import { ErrorNote } from "./ui";
import { formatDistance, formatDuree, lienNavigation, positionActuelle, type Coord } from "../lib/geo";

interface RouteResp {
  distance_km: number;
  duree_min: number;
  coordonnees: [number, number][];
}

/** Carte de l'espace + calcul d'itinéraire depuis la position de l'utilisateur. */
export function Itineraire({ dest, nom }: { dest: Coord; nom: string }) {
  const [moi, setMoi] = useState<Coord | null>(null);
  const [route, setRoute] = useState<RouteResp | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  async function calculer() {
    setBusy(true);
    setError("");
    try {
      const pos = await positionActuelle();
      setMoi(pos);
      const r = await api.get<RouteResp>("/geo/itineraire", {
        depart: `${pos.lat},${pos.lng}`,
        arrivee: `${dest.lat},${dest.lng}`,
        profil: "driving",
      });
      setRoute(r);
    } catch (e) {
      setError(e instanceof ApiError ? e.premierMessage : (e as Error).message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <div style={{ marginTop: 18 }}>
      <div style={{ fontSize: 13, fontWeight: 700, color: "#BD3100", marginBottom: 8 }}>Localisation</div>

      <MapView
        marker={{ lat: dest.lat, lng: dest.lng }}
        user={moi}
        route={route?.coordonnees}
        height={200}
      />

      {route && (
        <div
          style={{
            display: "flex", gap: 16, alignItems: "center", justifyContent: "center",
            background: "#FFF0E6", borderRadius: 12, padding: "10px 14px", marginTop: 10,
          }}
        >
          <span style={{ fontSize: 14, fontWeight: 700, color: "#BD3100" }}>
            🚗 {formatDistance(route.distance_km)}
          </span>
          <span style={{ color: "#CBB8AC" }}>·</span>
          <span style={{ fontSize: 14, fontWeight: 700, color: "#BD3100" }}>
            ⏱ {formatDuree(route.duree_min)}
          </span>
        </div>
      )}

      <ErrorNote message={error} />

      <div style={{ display: "flex", gap: 10, marginTop: 10 }}>
        <button
          onClick={calculer}
          disabled={busy}
          style={{
            flex: 1, padding: "12px", background: busy ? "#F1F5F9" : "#FC4E00", color: busy ? "#94A3B8" : "white",
            border: "none", borderRadius: 12, fontFamily: "inherit", fontSize: 13.5, fontWeight: 700, cursor: "pointer",
          }}
        >
          {busy ? "Calcul…" : route ? "Recalculer" : "Calculer l'itinéraire"}
        </button>
        <a
          href={lienNavigation(dest, moi)}
          target="_blank"
          rel="noopener noreferrer"
          style={{
            flex: 1, padding: "12px", background: "white", color: "#FC4E00", border: "1.5px solid #FC4E00",
            borderRadius: 12, fontSize: 13.5, fontWeight: 700, textAlign: "center", textDecoration: "none",
          }}
        >
          Ouvrir dans Maps
        </a>
      </div>
      <div style={{ fontSize: 11, color: "#94A3B8", marginTop: 6, textAlign: "center" }}>
        Itinéraire routier vers « {nom} »
      </div>
    </div>
  );
}
