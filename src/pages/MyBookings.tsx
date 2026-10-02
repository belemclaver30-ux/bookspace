import { useState } from "react";
import { Link } from "react-router-dom";
import { AppLayout } from "../components/AppLayout";
import { Icon } from "../components/Icons";
import { EmptyState, ErrorNote, Spinner, StatusBadge } from "../components/ui";
import { fcfa, dateLongue, plageHoraire } from "../lib/format";
import { useApi } from "../lib/useApi";
import type { Paginated, Reservation } from "../lib/types";

const FILTRES = [
  { id: "", label: "Toutes" },
  { id: "confirmee", label: "Confirmées" },
  { id: "en_attente", label: "En attente" },
  { id: "terminee", label: "Terminées" },
] as const;

export default function MyBookings() {
  const [statut, setStatut] = useState("");
  const { data, loading, error } = useApi<Paginated<Reservation>>("/reservations", { statut: statut || undefined });
  const reservations = data?.data ?? [];

  return (
    <AppLayout>
      <div style={{ background: "#BD3100", padding: "20px 20px 18px" }}>
        <div style={{ fontFamily: "Poppins, sans-serif", fontSize: 20, fontWeight: 700, color: "white" }}>Mes réservations</div>
      </div>

      <div style={{ padding: "14px 20px 8px", background: "white", borderBottom: "1px solid #F1F5F9", display: "flex", gap: 8, overflowX: "auto" }}>
        {FILTRES.map((f) => (
          <button
            key={f.id}
            onClick={() => setStatut(f.id)}
            style={{
              padding: "7px 13px", borderRadius: 20, whiteSpace: "nowrap",
              border: statut === f.id ? "none" : "1.5px solid #E2E8F0",
              background: statut === f.id ? "#FC4E00" : "white",
              color: statut === f.id ? "white" : "#475569", fontSize: 12, fontWeight: 600, cursor: "pointer",
            }}
          >
            {f.label}
          </button>
        ))}
      </div>

      <div style={{ flex: 1, overflowY: "auto", padding: "16px 20px 24px" }}>
        {loading && <Spinner />}
        <ErrorNote message={error} />
        {!loading && reservations.length === 0 && (
          <EmptyState title="Aucune réservation" subtitle="Réservez un espace depuis l'accueil." />
        )}
        {reservations.map((r) => (
          <Link
            key={r.id}
            to={r.statut === "en_attente" ? `/reservations/${r.id}/paiement` : `/reservations/${r.id}`}
            style={{ display: "block", background: "white", borderRadius: 16, padding: 16, marginBottom: 12, textDecoration: "none", boxShadow: "0 2px 10px rgba(120,40,15,0.06)" }}
          >
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", marginBottom: 8 }}>
              <div>
                <div style={{ fontSize: 15, fontWeight: 700, color: "#BD3100" }}>{r.espace?.nom ?? "Espace"}</div>
                <div style={{ fontSize: 12, color: "#94A3B8" }}>{r.reference}</div>
              </div>
              <StatusBadge statut={r.statut} />
            </div>
            <div style={{ display: "flex", alignItems: "center", gap: 6, fontSize: 12, color: "#64748B", marginBottom: 4 }}>
              <Icon.Calendar />{dateLongue(r.date_debut)}
            </div>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
              <span style={{ fontSize: 12, color: "#64748B" }}>{plageHoraire(r.date_debut, r.date_fin)}</span>
              <span style={{ fontSize: 13, fontWeight: 700, color: "#FC4E00" }}>{fcfa(r.prix_total)}</span>
            </div>
            {r.statut === "en_attente" && (
              <div style={{ marginTop: 10, fontSize: 12, fontWeight: 600, color: "#EB9F04" }}>
                → Payer l'acompte pour confirmer
              </div>
            )}
            {r.evaluable && !r.a_evalue && (
              <div style={{ marginTop: 10, display: "flex", alignItems: "center", gap: 6, fontSize: 12, fontWeight: 700, color: "#FC4E00" }}>
                <span style={{ color: "#F3BB00", fontSize: 15 }}>★</span> Donnez votre avis sur cet espace
              </div>
            )}
            {r.a_evalue && (
              <div style={{ marginTop: 10, fontSize: 12, color: "#94A3B8" }}>
                <span style={{ color: "#F3BB00" }}>★</span> Avis publié — merci !
              </div>
            )}
          </Link>
        ))}
      </div>
    </AppLayout>
  );
}
