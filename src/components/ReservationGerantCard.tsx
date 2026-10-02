import { useState } from "react";
import { api, ApiError } from "../lib/api";
import { Icon } from "./Icons";
import { ErrorNote, StatusBadge } from "./ui";
import { fcfa, dateLongue, plageHoraire } from "../lib/format";
import type { Reservation } from "../lib/types";

/**
 * Carte d'une réservation côté gérant, avec actions : reporter (déplacer vers
 * un autre créneau) et annuler (avec motif obligatoire — TDR §5.2).
 */
export function ReservationGerantCard({ resa, onChange }: { resa: Reservation; onChange: () => void }) {
  const [mode, setMode] = useState<"" | "reporter">("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  // Formulaire « reporter »
  const debut = new Date(resa.date_debut);
  const [date, setDate] = useState(toLocalInput(debut));
  const [duree, setDuree] = useState(String(resa.duree_heures));

  async function reporter() {
    setBusy(true);
    setError("");
    try {
      await api.post(`/gerant/reservations/${resa.id}/reporter`, {
        date_debut: new Date(date).toISOString(),
        duree_heures: Number(duree) !== resa.duree_heures ? Number(duree) : undefined,
      });
      setMode("");
      onChange();
    } catch (e) {
      setError(e instanceof ApiError ? e.premierMessage : "Report impossible.");
    } finally {
      setBusy(false);
    }
  }

  async function annuler() {
    const motif = prompt("Motif de l'annulation (communiqué au client) :");
    if (!motif) return;
    setBusy(true);
    setError("");
    try {
      await api.post(`/gerant/reservations/${resa.id}/annuler`, { motif });
      onChange();
    } catch (e) {
      setError(e instanceof ApiError ? e.premierMessage : "Annulation impossible.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <div style={{ background: "white", borderRadius: 14, padding: 16, boxShadow: "0 1px 4px rgba(120,40,15,0.06)" }}>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", marginBottom: 8 }}>
        <div>
          <div style={{ fontSize: 15, fontWeight: 700, color: "#BD3100" }}>{resa.espace?.nom}</div>
          <div style={{ fontSize: 12, color: "#94A3B8" }}>
            {resa.reference} · {resa.client?.name}
            {resa.origine === "gerant" && (
              <span style={{ marginLeft: 6, background: "#FFF0E6", color: "#BD3100", fontSize: 10, fontWeight: 700, padding: "2px 7px", borderRadius: 20 }}>
                Ajoutée manuellement
              </span>
            )}
          </div>
        </div>
        <StatusBadge statut={resa.statut} />
      </div>

      <div style={{ display: "flex", alignItems: "center", gap: 6, fontSize: 12, color: "#64748B", marginBottom: 6 }}>
        <Icon.Calendar />{dateLongue(resa.date_debut)} · {plageHoraire(resa.date_debut, resa.date_fin)}
      </div>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
        <span style={{ fontSize: 12, color: "#64748B" }}>Net {fcfa(resa.montant_reverse_gerant)}</span>
        <span style={{ fontSize: 13, fontWeight: 700, color: "#FC4E00" }}>{fcfa(resa.prix_total)}</span>
      </div>

      {resa.motif_annulation && (
        <div style={{ marginTop: 8, fontSize: 12, color: "#B91C1C" }}>Annulée : {resa.motif_annulation}</div>
      )}

      <ErrorNote message={error} />

      {(resa.reportable || resa.annulable) && mode === "" && (
        <div style={{ display: "flex", gap: 8, marginTop: 12 }}>
          {resa.reportable && (
            <button onClick={() => setMode("reporter")} disabled={busy} style={btn("#FFF0E6", "#BD3100")}>
              Reporter
            </button>
          )}
          {resa.annulable && (
            <button onClick={annuler} disabled={busy} style={btn("#FEE2E2", "#DC2626")}>
              Annuler
            </button>
          )}
        </div>
      )}

      {mode === "reporter" && (
        <div style={{ marginTop: 12, borderTop: "1px solid #F1F5F9", paddingTop: 12 }}>
          <div style={{ fontSize: 12, fontWeight: 700, color: "#BD3100", marginBottom: 8 }}>Nouveau créneau</div>
          <div style={{ display: "flex", gap: 8, flexWrap: "wrap", marginBottom: 10 }}>
            <input
              type="datetime-local"
              value={date}
              onChange={(e) => setDate(e.target.value)}
              style={{ flex: "1 1 180px", padding: "9px 10px", border: "1.5px solid #E2E8F0", borderRadius: 8, fontSize: 13 }}
            />
            <select
              value={duree}
              onChange={(e) => setDuree(e.target.value)}
              style={{ padding: "9px 10px", border: "1.5px solid #E2E8F0", borderRadius: 8, fontSize: 13 }}
            >
              {[1, 2, 3, 4, 5, 6].map((h) => <option key={h} value={h}>{h} h</option>)}
            </select>
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <button onClick={reporter} disabled={busy} style={{ ...btn("#FC4E00", "white"), flex: 1 }}>
              {busy ? "…" : "Confirmer le report"}
            </button>
            <button onClick={() => { setMode(""); setError(""); }} disabled={busy} style={btn("#F1F5F9", "#475569")}>
              Annuler
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

function btn(bg: string, color: string): React.CSSProperties {
  return { background: bg, color, border: "none", borderRadius: 9, padding: "8px 14px", fontSize: 12.5, fontWeight: 700, cursor: "pointer" };
}

/** Date -> valeur d'un <input type="datetime-local"> (heure locale). */
function toLocalInput(d: Date): string {
  const p = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
}
