import { useMemo, useState } from "react";
import { GerantLayout } from "../../components/GerantLayout";
import { CalendrierMois, ymd } from "../../components/CalendrierMois";
import { ReservationGerantCard } from "../../components/ReservationGerantCard";
import { ReservationManuelleForm } from "../../components/ReservationManuelleForm";
import { EmptyState, ErrorNote, Spinner } from "../../components/ui";
import { dateLongue } from "../../lib/format";
import { useApi } from "../../lib/useApi";
import type { Paginated, Reservation } from "../../lib/types";

const FILTRES = [
  { id: "", label: "Toutes" },
  { id: "confirmee", label: "Confirmées" },
  { id: "terminee", label: "Terminées" },
  { id: "en_attente", label: "En attente" },
  { id: "annulee", label: "Annulées" },
] as const;

const finDeMois = (d: Date) => new Date(d.getFullYear(), d.getMonth() + 1, 0);

export default function GerantReservations() {
  const [mois, setMois] = useState(() => new Date());
  const [jour, setJour] = useState<string | null>(null);
  const [statut, setStatut] = useState("");
  const [ajouter, setAjouter] = useState(false);

  const du = ymd(new Date(mois.getFullYear(), mois.getMonth(), 1));
  const au = ymd(finDeMois(mois));

  const { data, loading, error, reload } = useApi<Paginated<Reservation>>("/gerant/reservations", { du, au });
  const duMois = data?.data ?? [];

  const liste = useMemo(() => {
    return duMois
      .filter((r) => !jour || ymd(new Date(r.date_debut)) === jour)
      .filter((r) => !statut || r.statut === statut)
      .sort((a, b) => a.date_debut.localeCompare(b.date_debut));
  }, [duMois, jour, statut]);

  return (
    <GerantLayout>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", flexWrap: "wrap", gap: 12 }}>
        <h1 style={{ fontFamily: "Poppins, sans-serif", fontSize: 20, color: "#BD3100", margin: 0 }}>Réservations reçues</h1>
        {!ajouter && (
          <button
            onClick={() => setAjouter(true)}
            style={{ background: "#FC4E00", color: "white", border: "none", borderRadius: 10, padding: "9px 16px", fontSize: 13, fontWeight: 700, cursor: "pointer" }}
          >
            + Ajouter une réservation
          </button>
        )}
      </div>

      {ajouter && (
        <ReservationManuelleForm
          onCreated={() => { setAjouter(false); reload(); }}
          onCancel={() => setAjouter(false)}
        />
      )}

      {loading && <Spinner />}
      <ErrorNote message={error} />

      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(min(100%, 320px), 1fr))", gap: 16, alignItems: "start" }}>
        <CalendrierMois
          mois={mois}
          reservations={duMois}
          selected={jour}
          onSelect={setJour}
          onChangeMois={(d) => { setMois(d); setJour(null); }}
        />

        <div>
          <div style={{ display: "flex", gap: 8, marginBottom: 12, flexWrap: "wrap" }}>
            {FILTRES.map((f) => (
              <button
                key={f.id}
                onClick={() => setStatut(f.id)}
                style={{
                  padding: "6px 12px", borderRadius: 20, whiteSpace: "nowrap",
                  border: statut === f.id ? "none" : "1.5px solid #E2E8F0",
                  background: statut === f.id ? "#FC4E00" : "white",
                  color: statut === f.id ? "white" : "#475569", fontSize: 12, fontWeight: 600, cursor: "pointer",
                }}
              >
                {f.label}
              </button>
            ))}
          </div>

          <div style={{ fontSize: 12, color: "#94A3B8", fontWeight: 600, marginBottom: 10 }}>
            {jour ? (
              <>
                {dateLongue(jour)} — {liste.length} réservation{liste.length > 1 ? "s" : ""}
                <button onClick={() => setJour(null)} style={{ marginLeft: 8, background: "none", border: "none", color: "#FC4E00", fontWeight: 700, cursor: "pointer", fontSize: 12 }}>
                  voir tout le mois
                </button>
              </>
            ) : (
              `${liste.length} réservation${liste.length > 1 ? "s" : ""} ce mois-ci`
            )}
          </div>

          {!loading && liste.length === 0 && <EmptyState title="Aucune réservation" subtitle={jour ? "Rien ce jour-là." : "Rien ce mois-ci."} />}

          <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
            {liste.map((r) => (
              <ReservationGerantCard key={r.id} resa={r} onChange={reload} />
            ))}
          </div>
        </div>
      </div>
    </GerantLayout>
  );
}
