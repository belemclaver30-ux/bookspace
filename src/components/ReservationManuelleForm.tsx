import { useState } from "react";
import { api, ApiError } from "../lib/api";
import { ErrorNote } from "./ui";
import { useApi } from "../lib/useApi";
import type { Espace } from "../lib/types";

/**
 * Formulaire gérant : caler une réservation manuellement (client au comptoir
 * ou par téléphone qui n'a pas réservé sur le site). Confirmée directement,
 * sans compte client ni paiement en ligne.
 */
export function ReservationManuelleForm({ onCreated, onCancel }: { onCreated: () => void; onCancel: () => void }) {
  const { data } = useApi<{ data: Espace[] }>("/gerant/espaces");
  const espacesValides = (data?.data ?? []).filter((e) => e.statut === "valide");

  const [espaceId, setEspaceId] = useState("");
  const [date, setDate] = useState(() => toLocalInput(prochainCreneauRond()));
  const [duree, setDuree] = useState("1");
  const [nom, setNom] = useState("");
  const [telephone, setTelephone] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  async function envoyer() {
    if (!espaceId || !nom.trim() || !telephone.trim()) {
      setError("Espace, nom et téléphone du client sont requis.");
      return;
    }
    setBusy(true);
    setError("");
    try {
      await api.post(`/gerant/espaces/${espaceId}/reservations`, {
        date_debut: new Date(date).toISOString(),
        duree_heures: Number(duree),
        client_nom: nom.trim(),
        client_telephone: telephone.trim(),
      });
      onCreated();
    } catch (e) {
      setError(e instanceof ApiError ? e.premierMessage : "Création impossible.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <div style={{ background: "white", borderRadius: 14, padding: 16, boxShadow: "0 1px 4px rgba(120,40,15,0.06)", marginBottom: 16 }}>
      <div style={{ fontSize: 13, fontWeight: 700, color: "#BD3100", marginBottom: 10 }}>
        Caler une réservation manuellement
      </div>
      <div style={{ fontSize: 12, color: "#64748B", marginBottom: 12 }}>
        Pour un client qui a réservé par téléphone ou au comptoir. La réservation est confirmée immédiatement.
      </div>

      <ErrorNote message={error} />

      <div style={{ display: "grid", gap: 10, gridTemplateColumns: "repeat(auto-fit, minmax(160px, 1fr))", marginBottom: 12 }}>
        <select value={espaceId} onChange={(e) => setEspaceId(e.target.value)} style={inputStyle}>
          <option value="">Choisir un espace…</option>
          {espacesValides.map((e) => (
            <option key={e.id} value={e.id}>{e.nom}</option>
          ))}
        </select>
        <input type="datetime-local" value={date} onChange={(e) => setDate(e.target.value)} style={inputStyle} />
        <select value={duree} onChange={(e) => setDuree(e.target.value)} style={inputStyle}>
          {[1, 2, 3, 4, 5, 6].map((h) => <option key={h} value={h}>{h} h</option>)}
        </select>
        <input type="text" placeholder="Nom du client" value={nom} onChange={(e) => setNom(e.target.value)} style={inputStyle} />
        <input type="tel" placeholder="Téléphone du client" value={telephone} onChange={(e) => setTelephone(e.target.value)} style={inputStyle} />
      </div>

      <div style={{ display: "flex", gap: 8 }}>
        <button onClick={envoyer} disabled={busy} style={{ ...btn("#FC4E00", "white"), flex: 1 }}>
          {busy ? "…" : "Confirmer la réservation"}
        </button>
        <button onClick={onCancel} disabled={busy} style={btn("#F1F5F9", "#475569")}>
          Annuler
        </button>
      </div>
    </div>
  );
}

const inputStyle: React.CSSProperties = {
  padding: "9px 10px", border: "1.5px solid #E2E8F0", borderRadius: 8, fontSize: 13, fontFamily: "inherit",
};

function btn(bg: string, color: string): React.CSSProperties {
  return { background: bg, color, border: "none", borderRadius: 9, padding: "10px 14px", fontSize: 12.5, fontWeight: 700, cursor: "pointer" };
}

/** Prochaine heure ronde (dans l'heure qui suit), pour préremplir le créneau. */
function prochainCreneauRond(): Date {
  const d = new Date();
  d.setMinutes(0, 0, 0);
  d.setHours(d.getHours() + 1);
  return d;
}

function toLocalInput(d: Date): string {
  const p = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
}
