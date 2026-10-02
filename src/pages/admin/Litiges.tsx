import { useState } from "react";
import { AdminCard, AdminLayout } from "../../components/AdminLayout";
import { api, ApiError } from "../../lib/api";
import { EmptyState, ErrorNote, Spinner } from "../../components/ui";
import { dateLongue } from "../../lib/format";
import { useApi } from "../../lib/useApi";
import type { Paginated } from "../../lib/types";

interface Litige {
  id: number;
  sujet: string;
  description: string;
  statut: "ouvert" | "en_cours" | "resolu" | "ferme";
  resolution: string | null;
  created_at: string;
  reservation: { reference: string } | null;
  auteur: { name: string } | null;
}

const STATUTS = ["", "ouvert", "en_cours", "resolu", "ferme"];

export default function AdminLitiges() {
  const [statut, setStatut] = useState("");
  const { data, loading, error, reload } = useApi<Paginated<Litige>>("/admin/litiges", { statut: statut || undefined });
  const [busy, setBusy] = useState<number | null>(null);
  const [err, setErr] = useState("");

  async function resoudre(id: number, nouveauStatut: string) {
    const resolution = nouveauStatut !== "en_cours" ? prompt("Note de résolution :") ?? "" : "";
    setBusy(id);
    setErr("");
    try {
      await api.post(`/admin/litiges/${id}/resoudre`, { statut: nouveauStatut, resolution });
      reload();
    } catch (e) {
      setErr(e instanceof ApiError ? e.premierMessage : "Erreur.");
    } finally {
      setBusy(null);
    }
  }

  return (
    <AdminLayout>
      <h1 style={{ fontFamily: "Poppins, sans-serif", fontSize: 20, color: "#BD3100", marginTop: 0 }}>Litiges</h1>
      <div style={{ display: "flex", gap: 8, marginBottom: 16 }}>
        {STATUTS.map((s) => (
          <button
            key={s}
            onClick={() => setStatut(s)}
            style={{
              padding: "6px 12px", borderRadius: 8, border: "none", cursor: "pointer", fontSize: 12, fontWeight: 600,
              background: statut === s ? "#FC4E00" : "white", color: statut === s ? "white" : "#475569",
            }}
          >
            {s === "" ? "Tous" : s}
          </button>
        ))}
      </div>

      {loading && <Spinner />}
      <ErrorNote message={error || err} />
      {(data?.data.length ?? 0) === 0 && <EmptyState title="Aucun litige" />}
      {data?.data.map((l) => (
        <AdminCard key={l.id}>
          <div style={{ display: "flex", justifyContent: "space-between", gap: 12, flexWrap: "wrap" }}>
            <div style={{ flex: 1, minWidth: 240 }}>
              <div style={{ fontSize: 14, fontWeight: 700, color: "#BD3100" }}>{l.sujet}</div>
              <div style={{ fontSize: 12, color: "#94A3B8", marginBottom: 6 }}>
                {l.reservation?.reference ?? "—"} · ouvert par {l.auteur?.name ?? "?"} · {dateLongue(l.created_at)} · <b>{l.statut}</b>
              </div>
              <div style={{ fontSize: 13, color: "#475569" }}>{l.description}</div>
              {l.resolution && <div style={{ fontSize: 13, color: "#16A34A", marginTop: 6 }}>Résolution : {l.resolution}</div>}
            </div>
            {["ouvert", "en_cours"].includes(l.statut) && (
              <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
                {l.statut === "ouvert" && (
                  <button onClick={() => resoudre(l.id, "en_cours")} disabled={busy === l.id} style={btn("#CA8A04")}>Prendre en charge</button>
                )}
                <button onClick={() => resoudre(l.id, "resolu")} disabled={busy === l.id} style={btn("#16A34A")}>Résolu</button>
                <button onClick={() => resoudre(l.id, "ferme")} disabled={busy === l.id} style={btn("#64748B")}>Fermer</button>
              </div>
            )}
          </div>
        </AdminCard>
      ))}
    </AdminLayout>
  );
}

function btn(color: string): React.CSSProperties {
  return { background: color, color: "white", border: "none", borderRadius: 8, padding: "6px 12px", fontSize: 12, fontWeight: 600, cursor: "pointer" };
}
