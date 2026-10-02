import { useEffect, useRef, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { api, ApiError } from "../lib/api";
import { BackButton, ErrorNote, PrimaryBtn, Spinner } from "../components/ui";
import { fcfa, dateLongue, plageHoraire } from "../lib/format";
import { useApi } from "../lib/useApi";
import type { Reservation } from "../lib/types";

export default function Payment() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { data, loading, error: loadError } = useApi<{ data: Reservation }>(`/reservations/${id}`);
  const [busy, setBusy] = useState(false);
  const [verification, setVerification] = useState(false);
  const [error, setError] = useState("");

  // Écran quitté : on stoppe la boucle de sondage du statut de paiement.
  // (le mount doit remettre `true` : en StrictMode dev, React démonte/
  // remonte l'effet une fois à vide, ce qui figerait sinon le flag à false.)
  const vivant = useRef(true);
  useEffect(() => {
    vivant.current = true;
    return () => { vivant.current = false; };
  }, []);

  // Réservation déjà réglée / plus « en attente » : on va à la confirmation
  // (effet, jamais pendant le render). Couvre aussi le retour du client
  // depuis la page Yenga une fois le webhook reçu.
  useEffect(() => {
    const resa = data?.data;
    if (resa && resa.statut !== "en_attente") {
      navigate(`/reservations/${resa.id}/confirmation`, { replace: true });
    }
  }, [data, navigate]);

  if (loading) return <Screen><Spinner label="Chargement…" /></Screen>;
  if (loadError || !data) return <Screen><div style={{ padding: 24 }}><BackButton to="/reservations" /><ErrorNote message={loadError || "Réservation introuvable."} /></div></Screen>;

  const r = data.data;
  if (r.statut !== "en_attente") return null;

  const paiementEnCours = r.paiement?.statut === "en_cours";

  function versConfirmation() {
    if (vivant.current) navigate(`/reservations/${id}/confirmation`, { replace: true });
  }

  /** Yenga ne propose pas de vérification de statut à la demande : on
   * ressonde notre propre API, qui elle-même attend le webhook Yenga. */
  async function verifierStatut() {
    setVerification(true);
    setError("");
    try {
      for (let i = 0; i < 20 && vivant.current; i++) {
        const s = await api.get<{ data: Reservation }>(`/reservations/${id}/paiement/statut`);
        if (s.data.statut === "confirmee") {
          versConfirmation();
          return;
        }
        if (s.data.paiement?.statut === "echoue") {
          setError("Le paiement a échoué. Réessayez.");
          return;
        }
        await new Promise((res) => setTimeout(res, 3000));
      }
      if (vivant.current) {
        setError("Toujours en attente de confirmation. Réessayez dans un instant.");
      }
    } catch (err) {
      if (vivant.current) setError(err instanceof ApiError ? err.premierMessage : "Impossible de vérifier le paiement.");
    } finally {
      if (vivant.current) setVerification(false);
    }
  }

  async function payer() {
    setBusy(true);
    setError("");
    try {
      const res = await api.post<{ data: Reservation }>(`/reservations/${id}/paiement`, {});
      if (res.data.statut === "confirmee") {
        versConfirmation();
        return;
      }
      const lien = res.data.paiement?.lien_paiement;
      if (lien) {
        window.location.href = lien;
        return;
      }
      setError("Lien de paiement indisponible pour le moment. Réessayez.");
    } catch (err) {
      if (vivant.current) setError(err instanceof ApiError ? err.premierMessage : "Paiement impossible.");
    } finally {
      if (vivant.current) setBusy(false);
    }
  }

  return (
    <Screen>
      <div style={{ background: "white", borderBottom: "1px solid #F1F5F9", padding: "20px 20px 16px", display: "flex", alignItems: "center", gap: 12 }}>
        <BackButton to={-1} />
        <div style={{ fontFamily: "Poppins, sans-serif", fontSize: 17, fontWeight: 700, color: "#BD3100" }}>Paiement de l'acompte</div>
      </div>

      <div style={{ flex: 1, padding: "20px 20px 120px" }}>
        <div style={{ background: "#BD3100", borderRadius: 18, padding: 20, marginBottom: 24, textAlign: "center" }}>
          <div style={{ fontSize: 12, color: "rgba(255,255,255,0.5)", fontWeight: 500, marginBottom: 6 }}>Montant à payer</div>
          <div style={{ fontFamily: "Poppins, sans-serif", fontSize: 32, fontWeight: 800, color: "white" }}>{fcfa(r.montant_acompte)}</div>
          <div style={{ fontSize: 12, color: "rgba(255,255,255,0.45)", marginTop: 4 }}>
            Acompte {r.taux_acompte}% · {r.espace?.nom}
          </div>
        </div>

        <div style={{ fontSize: 13, fontWeight: 700, color: "#BD3100", marginBottom: 12 }}>Moyen de paiement</div>
        <div style={{ display: "flex", alignItems: "center", gap: 14, padding: 16, background: "white", borderRadius: 16, border: "1.5px solid #E2E8F0", marginBottom: 12 }}>
          <div style={{ width: 46, height: 46, borderRadius: 12, background: "#FC4E00", display: "flex", alignItems: "center", justifyContent: "center", fontWeight: 900, color: "white", fontSize: 15 }}>
            Y
          </div>
          <div style={{ flex: 1 }}>
            <div style={{ fontWeight: 700, color: "#BD3100", fontSize: 14 }}>Yenga</div>
            <div style={{ fontSize: 12, color: "#94A3B8" }}>Orange Money, Moov Money, Telecel Money…</div>
          </div>
        </div>
        <div style={{ fontSize: 12, color: "#64748B", lineHeight: 1.5, padding: "0 2px" }}>
          Vous serez redirigé vers la page de paiement sécurisée Yenga pour choisir votre opérateur et confirmer.
        </div>

        <div style={{ background: "#F8FAFC", borderRadius: 14, padding: 14, marginTop: 20 }}>
          {r.espace && <Row l={r.espace.nom} v={fcfa(r.prix_total)} />}
          <Row l={`${dateLongue(r.date_debut)} · ${plageHoraire(r.date_debut, r.date_fin)}`} v="" />
          <Row l={`Acompte (${r.taux_acompte}%)`} v={fcfa(r.montant_acompte)} />
          <div style={{ height: 1, background: "#E2E8F0", margin: "8px 0" }} />
          <Row l="Total" v={fcfa(r.montant_acompte)} bold />
        </div>

        {paiementEnCours && (
          <button
            onClick={verifierStatut}
            disabled={verification}
            style={{
              width: "100%", marginTop: 16, padding: "13px", background: "white", color: "#BD3100",
              border: "1.5px solid #E2E8F0", borderRadius: 14, fontFamily: "inherit", fontSize: 13.5,
              fontWeight: 700, cursor: verification ? "default" : "pointer",
            }}
          >
            {verification ? "Vérification…" : "J'ai terminé mon paiement, vérifier"}
          </button>
        )}

        <div style={{ height: 14 }} />
        <ErrorNote message={error} />
      </div>

      <div style={{ position: "sticky", bottom: 0, padding: "14px 20px calc(14px + env(safe-area-inset-bottom))", background: "white", borderTop: "1px solid #F1F5F9", marginTop: "auto" }}>
        <PrimaryBtn
          label={busy ? "Redirection…" : paiementEnCours ? "Reprendre le paiement Yenga" : `Payer ${fcfa(r.montant_acompte)}`}
          disabled={busy}
          onClick={payer}
        />
      </div>
    </Screen>
  );
}

function Row({ l, v, bold }: { l: string; v: string; bold?: boolean }) {
  if (!v && bold) return null;
  return (
    <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 8 }}>
      <span style={{ fontSize: 12, color: bold ? "#BD3100" : "#64748B", fontWeight: bold ? 700 : 400 }}>{l}</span>
      {v && <span style={{ fontSize: 12, fontWeight: bold ? 700 : 600, color: bold ? "#FC4E00" : "#334155" }}>{v}</span>}
    </div>
  );
}

function Screen({ children }: { children: React.ReactNode }) {
  return (
    <div style={{ minHeight: "100dvh", background: "#F8FAFC", display: "flex", justifyContent: "center" }}>
      <div style={{ width: "100%", maxWidth: 440, position: "relative", display: "flex", flexDirection: "column" }}>{children}</div>
    </div>
  );
}
