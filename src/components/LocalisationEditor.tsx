import { useState } from "react";
import { api, ApiError } from "../lib/api";
import { MapView } from "./MapView";
import { ErrorNote, PrimaryBtn } from "./ui";
import { positionActuelle, type Coord } from "../lib/geo";

interface ResultatGeo {
  label: string;
  latitude: number;
  longitude: number;
}

/**
 * Édition de la localisation d'un espace (gérant) :
 * recherche d'adresse, clic / glisser sur la carte, ou « ma position actuelle ».
 */
export function LocalisationEditor({
  espaceId, position, adresse, onSaved,
}: {
  espaceId: number | string;
  position: Coord | null;
  adresse: string;
  onSaved: () => void;
}) {
  const [point, setPoint] = useState<Coord | null>(position);
  const [adr, setAdr] = useState(adresse);
  const [recherche, setRecherche] = useState("");
  const [resultats, setResultats] = useState<ResultatGeo[]>([]);
  const [busy, setBusy] = useState<"" | "search" | "gps" | "save">("");
  const [err, setErr] = useState("");
  const [ok, setOk] = useState(false);

  async function chercher(e: React.FormEvent) {
    e.preventDefault();
    if (recherche.trim().length < 3) return;
    setBusy("search");
    setErr("");
    try {
      const r = await api.get<{ data: ResultatGeo[] }>("/geo/recherche", { q: recherche.trim() });
      setResultats(r.data);
      if (r.data.length === 0) setErr("Aucune adresse trouvée. Placez le marqueur manuellement sur la carte.");
    } catch (e) {
      setErr(e instanceof ApiError ? e.premierMessage : "Recherche impossible.");
    } finally {
      setBusy("");
    }
  }

  function choisir(r: ResultatGeo) {
    setPoint({ lat: r.latitude, lng: r.longitude });
    setAdr(r.label.split(",").slice(0, 3).join(", "));
    setResultats([]);
    setRecherche("");
  }

  async function maPosition() {
    setBusy("gps");
    setErr("");
    try {
      const pos = await positionActuelle();
      setPoint(pos);
      const r = await api.get<{ adresse: string | null }>("/geo/inverse", { lat: pos.lat, lng: pos.lng });
      if (r.adresse) setAdr(r.adresse.split(",").slice(0, 3).join(", "));
    } catch (e) {
      setErr((e as Error).message);
    } finally {
      setBusy("");
    }
  }

  async function enregistrer() {
    if (!point) {
      setErr("Placez d'abord le marqueur sur la carte.");
      return;
    }
    setBusy("save");
    setErr("");
    setOk(false);
    try {
      await api.patch(`/gerant/espaces/${espaceId}`, {
        latitude: point.lat,
        longitude: point.lng,
        adresse: adr.trim() || undefined,
      });
      setOk(true);
      onSaved();
    } catch (e) {
      setErr(e instanceof ApiError ? e.premierMessage : "Enregistrement impossible.");
    } finally {
      setBusy("");
    }
  }

  return (
    <div style={{ background: "white", borderRadius: 16, padding: 16, boxShadow: "0 2px 10px rgba(120,40,15,0.06)" }}>
      <div style={{ fontSize: 14, fontWeight: 700, color: "#BD3100", marginBottom: 4 }}>Localisation</div>
      <div style={{ fontSize: 12, color: "#94A3B8", marginBottom: 12 }}>
        Cherchez l'adresse, cliquez sur la carte ou déplacez le marqueur.
      </div>

      <ErrorNote message={err} />

      <form onSubmit={chercher} style={{ display: "flex", gap: 8, marginBottom: 8 }}>
        <input
          value={recherche}
          onChange={(e) => setRecherche(e.target.value)}
          placeholder="Ex. Ouaga 2000, Rue 28.104"
          style={{ flex: 1, padding: "10px 12px", border: "1.5px solid #E2E8F0", borderRadius: 10, fontSize: 14 }}
        />
        <button
          type="submit"
          disabled={busy === "search"}
          style={{ background: "#FC4E00", color: "white", border: "none", borderRadius: 10, padding: "0 14px", fontSize: 13, fontWeight: 600, cursor: "pointer" }}
        >
          {busy === "search" ? "…" : "Chercher"}
        </button>
      </form>

      {resultats.length > 0 && (
        <div style={{ border: "1px solid #E2E8F0", borderRadius: 10, marginBottom: 10, overflow: "hidden" }}>
          {resultats.map((r, idx) => (
            <button
              key={idx}
              onClick={() => choisir(r)}
              style={{
                display: "block", width: "100%", textAlign: "left", padding: "10px 12px", border: "none",
                borderBottom: idx < resultats.length - 1 ? "1px solid #F1F5F9" : "none",
                background: "white", cursor: "pointer", fontSize: 12.5, color: "#334155",
              }}
            >
              {r.label}
            </button>
          ))}
        </div>
      )}

      <button
        onClick={maPosition}
        disabled={busy === "gps"}
        style={{
          width: "100%", padding: "10px", background: "#FFF0E6", color: "#BD3100", border: "none",
          borderRadius: 10, fontSize: 13, fontWeight: 600, cursor: "pointer", marginBottom: 10,
        }}
      >
        {busy === "gps" ? "Localisation…" : "📍 Utiliser ma position actuelle"}
      </button>

      <MapView
        marker={point}
        onPick={setPoint}
        onMarkerMove={setPoint}
        center={point ?? undefined}
        zoom={point ? 15 : 12}
        height={240}
      />

      <div style={{ marginTop: 10 }}>
        <div style={{ fontSize: 11, fontWeight: 600, color: "#475569", marginBottom: 4 }}>ADRESSE AFFICHÉE</div>
        <input
          value={adr}
          onChange={(e) => setAdr(e.target.value)}
          style={{ width: "100%", padding: "10px 12px", border: "1.5px solid #E2E8F0", borderRadius: 10, fontSize: 14 }}
        />
      </div>

      {point && (
        <div style={{ fontSize: 11, color: "#94A3B8", marginTop: 6 }}>
          {point.lat.toFixed(5)}, {point.lng.toFixed(5)}
        </div>
      )}

      <div style={{ height: 12 }} />
      <PrimaryBtn label={busy === "save" ? "Enregistrement…" : "Enregistrer la localisation"} disabled={busy === "save"} onClick={enregistrer} />
      {ok && <div style={{ color: "#16A34A", fontSize: 13, fontWeight: 600, marginTop: 8 }}>✓ Localisation enregistrée</div>}
    </div>
  );
}
