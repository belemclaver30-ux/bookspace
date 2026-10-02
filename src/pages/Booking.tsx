import { useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { api, ApiError } from "../lib/api";
import { BackButton, ErrorNote, PrimaryBtn, Spinner } from "../components/ui";
import { fcfa } from "../lib/format";
import { useApi } from "../lib/useApi";
import type { Creneau, Espace, Reservation } from "../lib/types";

const JOURS_COURTS = ["D", "L", "M", "M", "J", "V", "S"];

export default function Booking() {
  const { id } = useParams();
  const navigate = useNavigate();

  const jours = useMemo(() => {
    const out: Date[] = [];
    const base = new Date();
    base.setHours(0, 0, 0, 0);
    for (let i = 1; i <= 14; i++) {
      const d = new Date(base);
      d.setDate(base.getDate() + i);
      out.push(d);
    }
    return out;
  }, []);

  const [dateIdx, setDateIdx] = useState(0);
  const [duree, setDuree] = useState(1);
  const [slot, setSlot] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  const jour = jours[dateIdx];
  const dateISO = toISODate(jour);

  const { data: espace } = useApi<{ data: Espace }>(`/espaces/${id}`);
  const { data: config } = useApi<{ deposit_rate: number }>("/parametres-publics");
  const { data: creneaux, loading: loadingSlots } = useApi<{ date: string; creneaux: Creneau[] }>(
    `/espaces/${id}/creneaux`,
    { date: dateISO, duree },
  );

  const tarif = espace?.data.tarif_horaire ?? 0;
  const tauxAcompte = config?.deposit_rate ?? 30;
  const prixTotal = tarif * duree;
  const acompte = Math.round((prixTotal * tauxAcompte) / 100);

  async function reserver() {
    if (!slot) return;
    setBusy(true);
    setError("");
    try {
      const dateDebut = `${dateISO}T${slot}:00`;
      const res = await api.post<{ data: Reservation }>("/reservations", {
        espace_id: Number(id),
        date_debut: dateDebut,
        duree_heures: duree,
      });
      navigate(`/reservations/${res.data.id}/paiement`);
    } catch (err) {
      setError(err instanceof ApiError ? err.premierMessage : "Réservation impossible.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <div style={{ minHeight: "100dvh", background: "#F8FAFC", display: "flex", justifyContent: "center" }}>
      <div style={{ width: "100%", maxWidth: 440, position: "relative", display: "flex", flexDirection: "column", paddingBottom: 0 }}>
        <div style={{ background: "white", borderBottom: "1px solid #F1F5F9", padding: "20px 20px 16px", display: "flex", alignItems: "center", gap: 12 }}>
          <BackButton to={-1} />
          <div>
            <div style={{ fontFamily: "Poppins, sans-serif", fontSize: 17, fontWeight: 700, color: "#BD3100" }}>Choisir un créneau</div>
            <div style={{ fontSize: 12, color: "#64748B" }}>{espace?.data.nom ?? "…"}</div>
          </div>
        </div>

        <div style={{ flex: 1, padding: "16px 20px" }}>
          <SectionTitle>Date</SectionTitle>
          <div style={{ display: "flex", gap: 6, overflowX: "auto", paddingBottom: 6, marginBottom: 20 }}>
            {jours.map((d, i) => (
              <button
                key={i}
                onClick={() => { setDateIdx(i); setSlot(null); }}
                style={{
                  minWidth: 46, padding: "10px 4px", borderRadius: 14, border: "none", cursor: "pointer",
                  background: dateIdx === i ? "#BD3100" : "white", boxShadow: "0 1px 4px rgba(120,40,15,0.06)",
                }}
              >
                <div style={{ fontSize: 10, color: dateIdx === i ? "rgba(255,255,255,0.6)" : "#94A3B8", fontWeight: 500, marginBottom: 4 }}>
                  {JOURS_COURTS[d.getDay()]}
                </div>
                <div style={{ fontSize: 15, fontWeight: 700, color: dateIdx === i ? "white" : "#334155" }}>{d.getDate()}</div>
              </button>
            ))}
          </div>

          <SectionTitle>Durée</SectionTitle>
          <div style={{ display: "flex", gap: 8, marginBottom: 20 }}>
            {[1, 2, 3].map((h) => (
              <button
                key={h}
                onClick={() => { setDuree(h); setSlot(null); }}
                style={{
                  flex: 1, padding: 10, borderRadius: 12,
                  border: duree === h ? "2px solid #FC4E00" : "1.5px solid #E2E8F0",
                  background: duree === h ? "#FFF0E6" : "white",
                  color: duree === h ? "#FC4E00" : "#64748B", fontWeight: 600, fontSize: 13, cursor: "pointer",
                }}
              >
                {h}h
              </button>
            ))}
          </div>

          <SectionTitle>Créneaux disponibles</SectionTitle>
          {loadingSlots ? (
            <Spinner />
          ) : (
            <div style={{ display: "grid", gridTemplateColumns: "repeat(4,1fr)", gap: 8, marginBottom: 22 }}>
              {(creneaux?.creneaux ?? []).map((c) => {
                const selected = slot === c.heure;
                return (
                  <button
                    key={c.heure}
                    disabled={!c.disponible}
                    onClick={() => setSlot(c.heure)}
                    style={{
                      padding: "10px 4px", borderRadius: 12, border: "none",
                      cursor: c.disponible ? "pointer" : "default",
                      background: !c.disponible ? "#F1F5F9" : selected ? "#FC4E00" : "white",
                      color: !c.disponible ? "#CBD5E1" : selected ? "white" : "#334155",
                      fontWeight: 600, fontSize: 13,
                      boxShadow: c.disponible ? "0 1px 4px rgba(120,40,15,0.07)" : "none",
                    }}
                  >
                    {c.heure}
                  </button>
                );
              })}
              {(creneaux?.creneaux ?? []).length === 0 && (
                <div style={{ gridColumn: "1 / -1", fontSize: 13, color: "#94A3B8" }}>Fermé ce jour-là.</div>
              )}
            </div>
          )}

          <div style={{ background: "white", borderRadius: 16, padding: 16, boxShadow: "0 2px 10px rgba(120,40,15,0.07)" }}>
            <div style={{ fontSize: 13, fontWeight: 700, color: "#BD3100", marginBottom: 12 }}>Résumé</div>
            {[
              ["Date", jour.toLocaleDateString("fr-FR", { weekday: "long", day: "numeric", month: "short" })],
              ["Heure", slot ? `${slot} – ${addHours(slot, duree)}` : "—"],
              ["Durée", `${duree} heure${duree > 1 ? "s" : ""}`],
              ["Prix total", fcfa(prixTotal)],
              [`Acompte (${tauxAcompte}%)`, fcfa(acompte)],
            ].map(([l, v]) => (
              <div key={l} style={{ display: "flex", justifyContent: "space-between", marginBottom: 8 }}>
                <span style={{ fontSize: 13, color: "#64748B" }}>{l}</span>
                <span style={{ fontSize: 13, fontWeight: 600, color: "#BD3100" }}>{v}</span>
              </div>
            ))}
            <div style={{ height: 1, background: "#F1F5F9", margin: "10px 0" }} />
            <div style={{ display: "flex", justifyContent: "space-between" }}>
              <span style={{ fontSize: 14, fontWeight: 700, color: "#BD3100" }}>À payer maintenant</span>
              <span style={{ fontSize: 14, fontWeight: 700, color: "#FC4E00" }}>{fcfa(acompte)}</span>
            </div>
          </div>

          <div style={{ height: 14 }} />
          <ErrorNote message={error} />
        </div>

        <div style={{ position: "sticky", bottom: 0, padding: "14px 20px calc(14px + env(safe-area-inset-bottom))", background: "white", borderTop: "1px solid #F1F5F9", marginTop: "auto" }}>
          <PrimaryBtn label={busy ? "Traitement…" : "Continuer"} disabled={!slot || busy} onClick={reserver} />
        </div>
      </div>
    </div>
  );
}

function SectionTitle({ children }: { children: React.ReactNode }) {
  return <div style={{ fontSize: 13, fontWeight: 700, color: "#BD3100", marginBottom: 10 }}>{children}</div>;
}

function toISODate(d: Date): string {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}

function addHours(hhmm: string, h: number): string {
  const [hh, mm] = hhmm.split(":").map(Number);
  return `${String(hh + h).padStart(2, "0")}:${String(mm).padStart(2, "0")}`;
}
