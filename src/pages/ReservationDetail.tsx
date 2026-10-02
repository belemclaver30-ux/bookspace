import { useState } from "react";
import { useParams } from "react-router-dom";
import { api, ApiError } from "../lib/api";
import { DemandeClient } from "../components/DemandeClient";
import { Icon } from "../components/Icons";
import { BackButton, ErrorNote, PrimaryBtn, Spinner, StatusBadge } from "../components/ui";
import { fcfa, dateLongue, plageHoraire } from "../lib/format";
import { useApi } from "../lib/useApi";
import type { Reservation } from "../lib/types";

export default function ReservationDetail() {
  const { id } = useParams();
  const { data, loading, error, reload } = useApi<{ data: Reservation }>(`/reservations/${id}`);
  const [busy, setBusy] = useState(false);
  const [actionError, setActionError] = useState("");
  const [note, setNote] = useState(5);
  const [commentaire, setCommentaire] = useState("");
  const [evalEnvoye, setEvalEnvoye] = useState(false);

  if (loading) return <Screen><Spinner label="Chargement…" /></Screen>;
  if (error || !data) return <Screen><div style={{ padding: 24 }}><BackButton to="/reservations" /><ErrorNote message={error || "Introuvable."} /></div></Screen>;

  const r = data.data;

  async function evaluer() {
    setBusy(true);
    setActionError("");
    try {
      await api.post(`/reservations/${id}/evaluation`, { note, commentaire: commentaire.trim() || undefined });
      setEvalEnvoye(true);
      reload();
    } catch (e) {
      setActionError(e instanceof ApiError ? e.premierMessage : "Envoi impossible.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <Screen>
      <div style={{ background: "white", borderBottom: "1px solid #F1F5F9", padding: "20px 20px 16px", display: "flex", alignItems: "center", gap: 12 }}>
        <BackButton to="/reservations" />
        <div style={{ fontFamily: "Poppins, sans-serif", fontSize: 17, fontWeight: 700, color: "#BD3100" }}>{r.reference}</div>
        <div style={{ marginLeft: "auto" }}><StatusBadge statut={r.statut} /></div>
      </div>

      <div style={{ padding: "18px 20px 40px" }}>
        <Card>
          <BigRow l="Espace" v={r.espace?.nom ?? "—"} />
          <BigRow l="Adresse" v={`${r.espace?.adresse ?? ""} ${r.espace?.ville ?? ""}`} />
          <BigRow l="Date" v={dateLongue(r.date_debut)} />
          <BigRow l="Horaire" v={plageHoraire(r.date_debut, r.date_fin)} />
          <BigRow l="Durée" v={`${r.duree_heures} h`} />
        </Card>

        <Card>
          <BigRow l="Prix total" v={fcfa(r.prix_total)} />
          <BigRow l={`Acompte (${r.taux_acompte}%)`} v={fcfa(r.montant_acompte)} />
          <BigRow l="Reste sur place" v={fcfa(r.prix_total - r.montant_acompte)} />
          {r.paiement && <BigRow l="Paiement" v={`${r.paiement.mode.replace("_", " ")} · ${r.paiement.statut}`} />}
        </Card>

        {r.motif_annulation && (
          <div style={{ background: "#FEF2F2", color: "#B91C1C", fontSize: 13, padding: 14, borderRadius: 12, marginBottom: 16 }}>
            Motif d'annulation : {r.motif_annulation}
          </div>
        )}

        <ErrorNote message={actionError} />

        {(r.annulable || r.demande) && <DemandeClient resa={r} onChange={reload} />}

        {/* Avis déjà déposé */}
        {r.mon_avis && (
          <Card>
            <div style={{ fontSize: 14, fontWeight: 700, color: "#BD3100", marginBottom: 8 }}>Votre avis</div>
            <div style={{ display: "flex", gap: 3, marginBottom: r.mon_avis.commentaire ? 8 : 0 }}>
              {[1, 2, 3, 4, 5].map((n) => (
                <span key={n} style={{ fontSize: 20, color: n <= r.mon_avis!.note ? "#F3BB00" : "#E2E8F0" }}>★</span>
              ))}
            </div>
            {r.mon_avis.commentaire && <div style={{ fontSize: 13, color: "#64748B", lineHeight: 1.6 }}>« {r.mon_avis.commentaire} »</div>}
          </Card>
        )}

        {/* Formulaire d'évaluation */}
        {r.evaluable && !r.mon_avis && !evalEnvoye && (
          <Card>
            <div style={{ fontSize: 14, fontWeight: 700, color: "#BD3100", marginBottom: 4 }}>Évaluer cet espace</div>
            <div style={{ fontSize: 12, color: "#94A3B8", marginBottom: 12 }}>Votre séjour est terminé — partagez votre expérience.</div>
            <div style={{ display: "flex", gap: 6, marginBottom: 12 }}>
              {[1, 2, 3, 4, 5].map((n) => (
                <button key={n} onClick={() => setNote(n)} style={{ background: "none", border: "none", cursor: "pointer", padding: 0 }}>
                  <span style={{ fontSize: 30, color: n <= note ? "#F3BB00" : "#E2E8F0" }}>★</span>
                </button>
              ))}
            </div>
            <textarea
              placeholder="Votre commentaire (optionnel)"
              value={commentaire}
              onChange={(e) => setCommentaire(e.target.value)}
              rows={3}
              style={{ width: "100%", padding: 12, border: "1.5px solid #E2E8F0", borderRadius: 12, fontFamily: "inherit", fontSize: 13, resize: "vertical", marginBottom: 12 }}
            />
            <PrimaryBtn label={busy ? "Envoi…" : "Publier mon avis"} disabled={busy} onClick={evaluer} />
          </Card>
        )}

        {evalEnvoye && !r.mon_avis && (
          <div style={{ background: "#DCFCE7", color: "#15803D", fontSize: 13, fontWeight: 600, padding: 14, borderRadius: 12, display: "flex", alignItems: "center", gap: 8 }}>
            <Icon.Star filled /> Merci pour votre avis !
          </div>
        )}
      </div>
    </Screen>
  );
}

function Card({ children }: { children: React.ReactNode }) {
  return <div style={{ background: "white", borderRadius: 16, padding: 16, marginBottom: 16, boxShadow: "0 2px 10px rgba(120,40,15,0.06)" }}>{children}</div>;
}

function BigRow({ l, v }: { l: string; v: string }) {
  return (
    <div style={{ display: "flex", justifyContent: "space-between", gap: 12, marginBottom: 10 }}>
      <span style={{ fontSize: 13, color: "#64748B" }}>{l}</span>
      <span style={{ fontSize: 13, fontWeight: 600, color: "#BD3100", textAlign: "right" }}>{v}</span>
    </div>
  );
}

function Screen({ children }: { children: React.ReactNode }) {
  return (
    <div style={{ minHeight: "100dvh", background: "#F8FAFC", display: "flex", justifyContent: "center" }}>
      <div style={{ width: "100%", maxWidth: 440, display: "flex", flexDirection: "column" }}>{children}</div>
    </div>
  );
}
