import { AdminCard, AdminLayout } from "../../components/AdminLayout";
import { AreaTrend, BarsH, COULEUR_STATUT, Donut } from "../../components/charts";
import { ErrorNote, Spinner } from "../../components/ui";
import { fcfa, LABEL_TYPE } from "../../lib/format";
import { useApi } from "../../lib/useApi";

interface Tb {
  utilisateurs: { clients: number; gerants: number };
  gerants_en_attente: number;
  espaces: { valides: number; en_attente: number };
  reservations: { total: number; confirmees: number };
  commissions_percues: number;
  litiges_ouverts: number;
  commissions_par_mois: { mois: string; commissions: number }[];
  espaces_par_type: { salle_repetition: number; terrain_foot: number };
  reservations_par_statut: Record<string, number>;
  top_espaces: { espace: string; chiffre_affaires: number }[];
}

const moisCourt = (ym: string) => {
  const [, m] = ym.split("-");
  return ["", "janv.", "févr.", "mars", "avr.", "mai", "juin", "juil.", "août", "sept.", "oct.", "nov.", "déc."][Number(m)];
};

export default function AdminDashboard() {
  const { data, loading, error } = useApi<Tb>("/admin/tableau-de-bord");

  return (
    <AdminLayout>
      <h1 style={{ fontFamily: "Poppins, sans-serif", fontSize: 20, color: "#BD3100", marginTop: 0 }}>Tableau de bord</h1>
      {loading && <Spinner />}
      <ErrorNote message={error} />
      {data && (
        <>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(min(100%, 148px), 1fr))", gap: 14, marginBottom: 16 }}>
            <Stat label="Clients" value={data.utilisateurs.clients} />
            <Stat label="Gérants" value={data.utilisateurs.gerants} />
            <Stat label="Gérants à valider" value={data.gerants_en_attente} alerte={data.gerants_en_attente > 0} />
            <Stat label="Espaces publiés" value={data.espaces.valides} />
            <Stat label="Espaces à valider" value={data.espaces.en_attente} alerte={data.espaces.en_attente > 0} />
            <Stat label="Réservations confirmées" value={data.reservations.confirmees} />
            <Stat label="Commissions perçues" value={fcfa(data.commissions_percues)} accent />
            <Stat label="Litiges ouverts" value={data.litiges_ouverts} alerte={data.litiges_ouverts > 0} />
          </div>

          <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(min(100%, 300px), 1fr))", gap: 16 }}>
            <AdminCard title="Commissions perçues (6 mois)">
              <AreaTrend data={data.commissions_par_mois.map((m) => ({ x: m.mois, value: m.commissions }))} xLabel={moisCourt} />
            </AdminCard>

            <AdminCard title="Réservations par statut">
              <Donut
                segments={(["confirmee", "terminee", "en_attente", "annulee"] as const).map((s) => ({
                  label: { confirmee: "Confirmées", terminee: "Terminées", en_attente: "En attente", annulee: "Annulées" }[s],
                  value: data.reservations_par_statut[s] ?? 0,
                  color: COULEUR_STATUT[s],
                }))}
              />
            </AdminCard>

            <AdminCard title="Espaces par type">
              <Donut
                segments={[
                  { label: LABEL_TYPE.salle_repetition, value: data.espaces_par_type.salle_repetition, color: "#FC4E00" },
                  { label: LABEL_TYPE.terrain_foot, value: data.espaces_par_type.terrain_foot, color: "#F3BB00" },
                ]}
              />
            </AdminCard>

            <AdminCard title="Top espaces (chiffre d'affaires)">
              <BarsH data={data.top_espaces.map((e) => ({ label: e.espace ?? "—", value: e.chiffre_affaires }))} />
            </AdminCard>
          </div>
        </>
      )}
    </AdminLayout>
  );
}

function Stat({ label, value, alerte, accent }: { label: string; value: number | string; alerte?: boolean; accent?: boolean }) {
  return (
    <AdminCard style={{ marginBottom: 0, background: accent ? "#BD3100" : "white" }}>
      <div style={{ fontSize: 12, color: accent ? "rgba(255,255,255,0.6)" : "#64748B", marginBottom: 6 }}>{label}</div>
      <div style={{ fontSize: 22, fontWeight: 800, color: accent ? "white" : alerte ? "#DC2626" : "#0F172A" }}>{value}</div>
    </AdminCard>
  );
}
