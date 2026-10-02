import { GerantLayout, Panel } from "../../components/GerantLayout";
import { AreaTrend, BarsH, COULEUR_STATUT, Donut, Jauge } from "../../components/charts";
import { ErrorNote, Spinner } from "../../components/ui";
import { fcfa } from "../../lib/format";
import { useApi } from "../../lib/useApi";
import { useAuth } from "../../context/AuthContext";

interface Stats {
  periode: { du: string; au: string };
  reservations: { total: number; confirmees: number; annulees: number; en_attente: number };
  revenus: { chiffre_affaires: number; acomptes_encaisses: number; commissions_plateforme: number; net_reverse: number };
  heures_reservees: number;
  taux_occupation: number;
  serie: { date: string; chiffre_affaires: number; reservations: number }[];
  repartition_statuts: { confirmee: number; terminee: number; en_attente: number; annulee: number };
  par_espace: { espace: string; reservations: number; chiffre_affaires: number }[];
}

const jourCourt = (iso: string) => {
  const d = new Date(iso);
  return `${d.getDate()}/${d.getMonth() + 1}`;
};

export default function GerantDashboard() {
  const { user } = useAuth();
  const { data, loading, error } = useApi<Stats>("/gerant/statistiques");
  const enAttente = user?.gerant?.statut_validation !== "valide";

  return (
    <GerantLayout>
      <h1 style={{ fontFamily: "Poppins, sans-serif", fontSize: 20, color: "#BD3100", marginTop: 0 }}>Tableau de bord</h1>

      {enAttente && (
        <div style={{ background: "#FEF0D6", color: "#854D0E", fontSize: 13, padding: 14, borderRadius: 12, marginBottom: 16 }}>
          Votre compte gérant est en attente de validation par l'administrateur.
        </div>
      )}

      {loading && <Spinner />}
      <ErrorNote message={error} />

      {data && (
        <>
          <div style={{ fontSize: 11, color: "#94A3B8", fontWeight: 600, textTransform: "uppercase", letterSpacing: 0.5, marginBottom: 10 }}>
            {data.periode.du} → {data.periode.au}
          </div>

          <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(min(100%, 148px), 1fr))", gap: 14, marginBottom: 16 }}>
            <Tile label="Chiffre d'affaires" value={fcfa(data.revenus.chiffre_affaires)} accent />
            <Tile label="Net à recevoir" value={fcfa(data.revenus.net_reverse)} />
            <Tile label="Réservations confirmées" value={String(data.reservations.confirmees)} />
            <Tile label="Heures réservées" value={`${data.heures_reservees} h`} />
          </div>

          <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(min(100%, 300px), 1fr))", gap: 16 }}>
            <Panel title="Évolution du chiffre d'affaires">
              <AreaTrend data={data.serie.map((s) => ({ x: s.date, value: s.chiffre_affaires }))} xLabel={jourCourt} />
            </Panel>

            <Panel title="Répartition des réservations">
              <Donut
                segments={[
                  { label: "Confirmées", value: data.repartition_statuts.confirmee, color: COULEUR_STATUT.confirmee },
                  { label: "Terminées", value: data.repartition_statuts.terminee, color: COULEUR_STATUT.terminee },
                  { label: "En attente", value: data.repartition_statuts.en_attente, color: COULEUR_STATUT.en_attente },
                  { label: "Annulées", value: data.repartition_statuts.annulee, color: COULEUR_STATUT.annulee },
                ]}
              />
            </Panel>

            <Panel title="Chiffre d'affaires par espace">
              <BarsH data={data.par_espace.map((e) => ({ label: e.espace ?? "—", value: e.chiffre_affaires }))} />
            </Panel>

            <Panel title="Taux d'occupation">
              <Jauge pct={data.taux_occupation} label="des heures ouvrées réservées" />
              <div style={{ fontSize: 12, color: "#64748B", textAlign: "center", marginTop: 6 }}>
                Commission plateforme prélevée : <b>{fcfa(data.revenus.commissions_plateforme)}</b>
              </div>
            </Panel>
          </div>
        </>
      )}
    </GerantLayout>
  );
}

function Tile({ label, value, accent }: { label: string; value: string; accent?: boolean }) {
  return (
    <div style={{ background: accent ? "#BD3100" : "white", borderRadius: 14, padding: 16, boxShadow: "0 1px 4px rgba(120,40,15,0.05)" }}>
      <div style={{ fontSize: 11, color: accent ? "rgba(255,255,255,0.55)" : "#94A3B8", marginBottom: 4 }}>{label}</div>
      <div style={{ fontSize: 18, fontWeight: 800, color: accent ? "white" : "#0F172A" }}>{value}</div>
    </div>
  );
}
