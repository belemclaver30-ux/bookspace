import { useState } from "react";
import { api, ApiError } from "../lib/api";
import { ErrorNote, OutlineBtn } from "./ui";
import { dateLongue, heure } from "../lib/format";
import type { Reservation } from "../lib/types";

/**
 * Actions du client sur sa réservation :
 * - `en_attente` (non payée) : annulation directe.
 * - `confirmee` : demande d'annulation ou de report, à valider par le gérant.
 */
export function DemandeClient({ resa, onChange }: { resa: Reservation; onChange: () => void }) {
  const [mode, setMode] = useState<"" | "annulation" | "report">("");
  const [motif, setMotif] = useState("");
  const [date, setDate] = useState(toLocalInput(new Date(resa.date_debut)));
  const [duree, setDuree] = useState(String(resa.duree_heures));
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  const d = resa.demande;

  async function annulerDirect() {
    if (!confirm("Annuler cette réservation ?")) return;
    await agir(() => api.post(`/reservations/${resa.id}/annuler`, { motif: "Annulée par le client" }));
  }

  async function envoyerDemande(type: "annulation" | "report") {
    await agir(() =>
      api.post(`/reservations/${resa.id}/demandes`, {
        type,
        motif: motif.trim() || undefined,
        date_debut: type === "report" ? new Date(date).toISOString() : undefined,
        duree_heures: type === "report" && Number(duree) !== resa.duree_heures ? Number(duree) : undefined,
      }),
    );
    setMode("");
    setMotif("");
  }

  async function retirer() {
    if (!d) return;
    await agir(() => api.del(`/demandes/${d.id}`));
  }

  async function agir(fn: () => Promise<unknown>) {
    setBusy(true);
    setError("");
    try {
      await fn();
      onChange();
    } catch (e) {
      setError(e instanceof ApiError ? e.premierMessage : "Action impossible.");
    } finally {
      setBusy(false);
    }
  }

  // ── Demande en attente ────────────────────────────────────────────────────
  if (d?.statut === "en_attente") {
    return (
      <div style={{ background: "#FEF0D6", borderRadius: 14, padding: 16, marginBottom: 12 }}>
        <div style={{ fontSize: 13, fontWeight: 700, color: "#854D0E" }}>
          Demande {d.type === "annulation" ? "d'annulation" : "de report"} envoyée
        </div>
        <div style={{ fontSize: 12, color: "#92700F", marginTop: 4 }}>
          En attente de la validation du gérant.
          {d.type === "report" && d.nouvelle_date_debut && (
            <> Nouveau créneau proposé : <b>{dateLongue(d.nouvelle_date_debut)} à {heure(d.nouvelle_date_debut)}</b>.</>
          )}
        </div>
        {d.motif && <div style={{ fontSize: 12, color: "#92700F", marginTop: 4 }}>Votre motif : « {d.motif} »</div>}
        <ErrorNote message={error} />
        <button
          onClick={retirer}
          disabled={busy}
          style={{ marginTop: 10, background: "white", border: "1.5px solid #EAB308", color: "#854D0E", borderRadius: 9, padding: "7px 14px", fontSize: 12.5, fontWeight: 700, cursor: "pointer" }}
        >
          Retirer la demande
        </button>
      </div>
    );
  }

  const refusee = d?.statut === "refusee";

  return (
    <div style={{ marginBottom: 12 }}>
      {refusee && (
        <div style={{ background: "#FEE2E2", borderRadius: 14, padding: 14, marginBottom: 10, fontSize: 12.5, color: "#B91C1C" }}>
          Le gérant a refusé votre demande {d!.type === "annulation" ? "d'annulation" : "de report"}.
          {d!.reponse_gerant && <> Message : « {d!.reponse_gerant} »</>}
        </div>
      )}

      <ErrorNote message={error} />

      {/* Réservation non confirmée : annulation directe */}
      {resa.statut === "en_attente" && (
        <OutlineBtn label={busy ? "…" : "Annuler la réservation"} onClick={annulerDirect} />
      )}

      {/* Réservation confirmée : demandes soumises au gérant */}
      {resa.statut === "confirmee" && resa.annulable && mode === "" && (
        <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
          <button onClick={() => setMode("report")} disabled={busy} style={btn("#FFF0E6", "#BD3100")}>Demander un report</button>
          <button onClick={() => setMode("annulation")} disabled={busy} style={btn("#FEE2E2", "#DC2626")}>Demander l'annulation</button>
        </div>
      )}

      {mode === "annulation" && (
        <div style={{ background: "white", borderRadius: 14, padding: 14, boxShadow: "0 1px 4px rgba(120,40,15,0.06)" }}>
          <div style={{ fontSize: 13, fontWeight: 700, color: "#BD3100", marginBottom: 8 }}>Demande d'annulation</div>
          <textarea
            placeholder="Motif (facultatif, transmis au gérant)"
            value={motif}
            onChange={(e) => setMotif(e.target.value)}
            rows={2}
            style={{ width: "100%", padding: 10, border: "1.5px solid #E2E8F0", borderRadius: 10, fontFamily: "inherit", fontSize: 13, resize: "vertical", marginBottom: 10 }}
          />
          <div style={{ display: "flex", gap: 8 }}>
            <button onClick={() => envoyerDemande("annulation")} disabled={busy} style={{ ...btn("#DC2626", "white"), flex: 1 }}>
              {busy ? "…" : "Envoyer la demande"}
            </button>
            <button onClick={() => setMode("")} disabled={busy} style={btn("#F1F5F9", "#475569")}>Annuler</button>
          </div>
        </div>
      )}

      {mode === "report" && (
        <div style={{ background: "white", borderRadius: 14, padding: 14, boxShadow: "0 1px 4px rgba(120,40,15,0.06)" }}>
          <div style={{ fontSize: 13, fontWeight: 700, color: "#BD3100", marginBottom: 8 }}>Demande de report</div>
          <div style={{ display: "flex", gap: 8, flexWrap: "wrap", marginBottom: 10 }}>
            <input type="datetime-local" value={date} onChange={(e) => setDate(e.target.value)}
              style={{ flex: "1 1 170px", padding: "9px 10px", border: "1.5px solid #E2E8F0", borderRadius: 8, fontSize: 13 }} />
            <select value={duree} onChange={(e) => setDuree(e.target.value)}
              style={{ padding: "9px 10px", border: "1.5px solid #E2E8F0", borderRadius: 8, fontSize: 13 }}>
              {[1, 2, 3, 4, 5, 6].map((h) => <option key={h} value={h}>{h} h</option>)}
            </select>
          </div>
          <textarea
            placeholder="Motif (facultatif)"
            value={motif}
            onChange={(e) => setMotif(e.target.value)}
            rows={2}
            style={{ width: "100%", padding: 10, border: "1.5px solid #E2E8F0", borderRadius: 10, fontFamily: "inherit", fontSize: 13, resize: "vertical", marginBottom: 10 }}
          />
          <div style={{ display: "flex", gap: 8 }}>
            <button onClick={() => envoyerDemande("report")} disabled={busy} style={{ ...btn("#FC4E00", "white"), flex: 1 }}>
              {busy ? "…" : "Envoyer la demande"}
            </button>
            <button onClick={() => setMode("")} disabled={busy} style={btn("#F1F5F9", "#475569")}>Annuler</button>
          </div>
        </div>
      )}
    </div>
  );
}

function btn(bg: string, color: string): React.CSSProperties {
  return { background: bg, color, border: "none", borderRadius: 9, padding: "9px 14px", fontSize: 12.5, fontWeight: 700, cursor: "pointer" };
}

function toLocalInput(d: Date): string {
  const p = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
}
