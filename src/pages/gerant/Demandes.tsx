import { useState } from "react";
import { GerantLayout } from "../../components/GerantLayout";
import { api, ApiError } from "../../lib/api";
import { EmptyState, ErrorNote, Spinner } from "../../components/ui";
import { fcfa, dateLongue, heure, plageHoraire } from "../../lib/format";
import { useApi } from "../../lib/useApi";
import type { DemandeModification, Paginated } from "../../lib/types";

const FILTRES = [
  { id: "en_attente", label: "À traiter" },
  { id: "acceptee", label: "Acceptées" },
  { id: "refusee", label: "Refusées" },
] as const;

export default function GerantDemandes() {
  const [statut, setStatut] = useState("en_attente");
  const { data, loading, error, reload } = useApi<Paginated<DemandeModification>>("/gerant/demandes", { statut });
  const demandes = data?.data ?? [];
  const [busy, setBusy] = useState<number | null>(null);
  const [err, setErr] = useState("");

  async function traiter(d: DemandeModification, action: "accepter" | "refuser") {
    let reponse: string | undefined;
    if (action === "refuser") {
      const r = prompt("Message pour le client (facultatif) :");
      if (r === null) return;
      reponse = r || undefined;
    } else if (!confirm(
      d.type === "annulation"
        ? "Accepter : la réservation sera annulée et le client prévenu."
        : "Accepter : la réservation sera déplacée au créneau proposé.",
    )) return;

    setBusy(d.id);
    setErr("");
    try {
      await api.post(`/gerant/demandes/${d.id}/${action}`, action === "refuser" ? { reponse } : undefined);
      reload();
    } catch (e) {
      setErr(e instanceof ApiError ? e.premierMessage : "Action impossible.");
    } finally {
      setBusy(null);
    }
  }

  return (
    <GerantLayout>
      <h1 style={{ fontFamily: "Poppins, sans-serif", fontSize: 20, color: "#BD3100", marginTop: 0 }}>Demandes des clients</h1>
      <div style={{ fontSize: 13, color: "#64748B", marginBottom: 16 }}>
        Annulations et reports demandés sur des réservations confirmées. À vous de valider.
      </div>

      <div style={{ display: "flex", gap: 8, marginBottom: 16, flexWrap: "wrap" }}>
        {FILTRES.map((f) => (
          <button
            key={f.id}
            onClick={() => setStatut(f.id)}
            style={{
              padding: "6px 14px", borderRadius: 20,
              border: statut === f.id ? "none" : "1.5px solid #E2E8F0",
              background: statut === f.id ? "#FC4E00" : "white",
              color: statut === f.id ? "white" : "#475569", fontSize: 12, fontWeight: 600, cursor: "pointer",
            }}
          >
            {f.label}
          </button>
        ))}
      </div>

      {loading && <Spinner />}
      <ErrorNote message={error || err} />
      {!loading && demandes.length === 0 && (
        <EmptyState title="Aucune demande" subtitle={statut === "en_attente" ? "Rien à traiter pour le moment." : undefined} />
      )}

      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(min(100%, 340px), 1fr))", gap: 16 }}>
        {demandes.map((d) => {
          const r = d.reservation;
          return (
            <div key={d.id} style={{ background: "white", borderRadius: 14, padding: 16, boxShadow: "0 1px 4px rgba(120,40,15,0.06)" }}>
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", marginBottom: 8 }}>
                <div>
                  <div style={{ fontSize: 15, fontWeight: 700, color: "#BD3100" }}>
                    {d.type === "annulation" ? "Annulation" : "Report"}
                  </div>
                  <div style={{ fontSize: 12, color: "#94A3B8" }}>
                    {r?.espace?.nom} · {r?.reference} · {d.demandeur?.name}
                  </div>
                </div>
                <span style={{
                  fontSize: 11, fontWeight: 700, padding: "3px 9px", borderRadius: 20,
                  background: d.statut === "en_attente" ? "#FEF0D6" : d.statut === "acceptee" ? "#DCFCE7" : "#FEE2E2",
                  color: d.statut === "en_attente" ? "#EB9F04" : d.statut === "acceptee" ? "#16A34A" : "#DC2626",
                }}>
                  {d.statut === "en_attente" ? "À traiter" : d.statut === "acceptee" ? "Acceptée" : "Refusée"}
                </span>
              </div>

              {r && (
                <div style={{ fontSize: 12, color: "#64748B", marginBottom: 6 }}>
                  Créneau actuel : {dateLongue(r.date_debut)} · {plageHoraire(r.date_debut, r.date_fin)} · {fcfa(r.prix_total)}
                </div>
              )}

              {d.type === "report" && d.nouvelle_date_debut && (
                <div style={{ fontSize: 13, fontWeight: 600, color: "#FC4E00", marginBottom: 6 }}>
                  → Nouveau créneau souhaité : {dateLongue(d.nouvelle_date_debut)} à {heure(d.nouvelle_date_debut)}
                  {d.nouvelle_duree_heures ? ` (${d.nouvelle_duree_heures} h)` : ""}
                </div>
              )}

              {d.motif && (
                <div style={{ fontSize: 12, color: "#475569", background: "#F8FAFC", borderRadius: 8, padding: "8px 10px", marginBottom: 8 }}>
                  « {d.motif} »
                </div>
              )}

              {d.reponse_gerant && (
                <div style={{ fontSize: 12, color: "#B91C1C", marginBottom: 8 }}>Votre réponse : « {d.reponse_gerant} »</div>
              )}

              {d.statut === "en_attente" && (
                <div style={{ display: "flex", gap: 8, marginTop: 10 }}>
                  <button onClick={() => traiter(d, "accepter")} disabled={busy === d.id}
                    style={{ flex: 1, background: "#16A34A", color: "white", border: "none", borderRadius: 9, padding: "9px 0", fontSize: 13, fontWeight: 700, cursor: "pointer" }}>
                    {busy === d.id ? "…" : "Accepter"}
                  </button>
                  <button onClick={() => traiter(d, "refuser")} disabled={busy === d.id}
                    style={{ flex: 1, background: "#FEE2E2", color: "#DC2626", border: "none", borderRadius: 9, padding: "9px 0", fontSize: 13, fontWeight: 700, cursor: "pointer" }}>
                    Refuser
                  </button>
                </div>
              )}
            </div>
          );
        })}
      </div>
    </GerantLayout>
  );
}
