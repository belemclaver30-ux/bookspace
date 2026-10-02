import { Link, useParams } from "react-router-dom";
import { Icon } from "../components/Icons";
import { ErrorNote, PrimaryBtn, OutlineBtn, Spinner } from "../components/ui";
import { fcfa, dateLongue, plageHoraire } from "../lib/format";
import { useApi } from "../lib/useApi";
import type { Reservation } from "../lib/types";

export default function Confirmation() {
  const { id } = useParams();
  const { data, loading, error } = useApi<{ data: Reservation }>(`/reservations/${id}`);

  return (
    <div style={{ minHeight: "100dvh", background: "white", display: "flex", justifyContent: "center" }}>
      <div style={{ width: "100%", maxWidth: 440, display: "flex", flexDirection: "column", alignItems: "center", padding: "0 28px 32px" }}>
        {loading && <Spinner label="Chargement…" />}
        {error && <div style={{ paddingTop: 40 }}><ErrorNote message={error} /></div>}
        {data && (
          <>
            <div className="animate-fadeinup" style={{ flex: 1, display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "center", textAlign: "center" }}>
              <div style={{ width: 88, height: 88, borderRadius: "50%", background: "#16A34A", display: "flex", alignItems: "center", justifyContent: "center", marginBottom: 24, boxShadow: "0 12px 40px rgba(22,163,74,0.4)" }}>
                <Icon.Check />
              </div>
              <div style={{ fontFamily: "Poppins, sans-serif", fontSize: 24, fontWeight: 800, color: "#BD3100", marginBottom: 8 }}>
                Réservation confirmée !
              </div>
              <div style={{ fontSize: 14, color: "#64748B", lineHeight: 1.6, marginBottom: 28 }}>
                Votre créneau est bloqué. Une confirmation a été envoyée au gérant et à vous-même.
              </div>

              <div style={{ width: "100%", background: "#F8FAFC", borderRadius: 16, padding: 18, textAlign: "left" }}>
                <Row l="Référence" v={data.data.reference} />
                <Row l="Espace" v={data.data.espace?.nom ?? "—"} />
                <Row l="Date" v={dateLongue(data.data.date_debut)} />
                <Row l="Horaire" v={plageHoraire(data.data.date_debut, data.data.date_fin)} />
                <Row l="Acompte payé" v={fcfa(data.data.montant_acompte)} />
                <Row l="Reste à régler sur place" v={fcfa(data.data.prix_total - data.data.montant_acompte)} />
              </div>
            </div>

            <div style={{ width: "100%", display: "flex", flexDirection: "column", gap: 12 }}>
              <Link to="/reservations" style={{ textDecoration: "none" }}><PrimaryBtn label="Voir mes réservations" /></Link>
              <Link to="/app" style={{ textDecoration: "none" }}><OutlineBtn label="Retour à l'accueil" /></Link>
            </div>
          </>
        )}
      </div>
    </div>
  );
}

function Row({ l, v }: { l: string; v: string }) {
  return (
    <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 10 }}>
      <span style={{ fontSize: 13, color: "#64748B" }}>{l}</span>
      <span style={{ fontSize: 13, fontWeight: 600, color: "#BD3100" }}>{v}</span>
    </div>
  );
}
