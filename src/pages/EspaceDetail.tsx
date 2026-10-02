import { useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { Icon } from "../components/Icons";
import { Itineraire } from "../components/Itineraire";
import { BackButton, ErrorNote, PrimaryBtn, Spinner } from "../components/ui";
import { fcfa, LABEL_TYPE } from "../lib/format";
import { useApi } from "../lib/useApi";
import { useAuth } from "../context/AuthContext";
import type { Espace } from "../lib/types";

const PLACEHOLDER = "https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=800&h=480&fit=crop&auto=format";

export default function EspaceDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const { data: espace, loading, error } = useApi<{ data: Espace }>(`/espaces/${id}`);
  const [tab, setTab] = useState<"info" | "equip" | "avis">("info");
  const [heroIdx, setHeroIdx] = useState(0);

  if (loading) return <FullScreen><Spinner label="Chargement…" /></FullScreen>;
  if (error || !espace) return <FullScreen><div style={{ padding: 24 }}><BackButton to="/app" /><ErrorNote message={error || "Espace introuvable."} /></div></FullScreen>;

  const e = espace.data;
  const galerie = (e.photos && e.photos.length > 0 ? e.photos : [e.photo_principale ?? PLACEHOLDER]).filter(Boolean) as string[];
  const img = galerie[Math.min(heroIdx, galerie.length - 1)] ?? PLACEHOLDER;
  const peutReserver = user?.role === "client";

  return (
    <div style={{ minHeight: "100dvh", background: "white", display: "flex", justifyContent: "center" }}>
      <div style={{ width: "100%", maxWidth: 440, position: "relative", display: "flex", flexDirection: "column", paddingBottom: 0 }}>
        <div style={{ position: "relative", height: 240, flexShrink: 0 }}>
          <img src={img} alt={e.nom} style={{ width: "100%", height: "100%", objectFit: "cover" }} />
          <div style={{ position: "absolute", inset: 0, background: "linear-gradient(to bottom, rgba(120,40,15,0.35) 0%, transparent 45%)" }} />
          <div style={{ position: "absolute", top: 16, left: 16 }}><BackButton to={-1} /></div>
          {galerie.length > 1 && (
            <div style={{ position: "absolute", bottom: 10, left: 12, right: 12, display: "flex", gap: 6, overflowX: "auto", paddingBottom: 2 }}>
              {galerie.map((u, i) => (
                <button
                  key={i}
                  onClick={() => setHeroIdx(i)}
                  style={{
                    width: 52, height: 40, borderRadius: 8, overflow: "hidden", flexShrink: 0, padding: 0,
                    border: i === Math.min(heroIdx, galerie.length - 1) ? "2px solid white" : "2px solid rgba(255,255,255,0.5)",
                    cursor: "pointer", background: "none",
                  }}
                >
                  <img src={u} alt="" style={{ width: "100%", height: "100%", objectFit: "cover" }} />
                </button>
              ))}
            </div>
          )}
        </div>

        <div style={{ flex: 1, padding: "20px 20px 0" }}>
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", marginBottom: 8 }}>
            <div>
              <div style={{ fontFamily: "Poppins, sans-serif", fontSize: 20, fontWeight: 700, color: "#BD3100" }}>{e.nom}</div>
              <div style={{ display: "flex", alignItems: "center", gap: 4, color: "#64748B", fontSize: 13, marginTop: 3 }}>
                <Icon.Location />{e.adresse} · {e.ville}
              </div>
            </div>
            <div style={{ textAlign: "right" }}>
              <div style={{ fontSize: 18, fontWeight: 700, color: "#FC4E00" }}>{e.tarif_horaire.toLocaleString("fr-FR")}</div>
              <div style={{ fontSize: 11, color: "#94A3B8" }}>FCFA / heure</div>
            </div>
          </div>

          <div style={{ display: "flex", alignItems: "center", gap: 6, marginBottom: 18, padding: "10px 14px", background: "#F8FAFC", borderRadius: 12 }}>
            <Icon.Star filled />
            <span style={{ fontWeight: 700, fontSize: 14, color: "#374151" }}>{e.note_moyenne.toFixed(1)}</span>
            <span style={{ color: "#94A3B8", fontSize: 12 }}>
              · {e.nb_avis} avis · {LABEL_TYPE[e.type]}
              {e.gerant?.verifie ? " · Gérant vérifié ✓" : ""}
            </span>
          </div>

          <div style={{ display: "flex", marginBottom: 18, borderBottom: "2px solid #F1F5F9" }}>
            {([["info", "Informations"], ["equip", "Équipements"], ["avis", `Avis (${e.avis?.length ?? 0})`]] as const).map(([k, label]) => (
              <button
                key={k}
                onClick={() => setTab(k)}
                style={{
                  flex: 1, padding: "10px 0", border: "none", background: "none", fontFamily: "inherit",
                  fontSize: 13, fontWeight: 600, cursor: "pointer",
                  color: tab === k ? "#FC4E00" : "#94A3B8",
                  borderBottom: tab === k ? "2px solid #FC4E00" : "2px solid transparent", marginBottom: -2,
                }}
              >
                {label}
              </button>
            ))}
          </div>

          {tab === "info" && (
            <div style={{ fontSize: 13, color: "#64748B", lineHeight: 1.7 }}>
              {e.description || "Aucune description fournie."}
              <div style={{ marginTop: 16, display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10 }}>
                {e.salle && (
                  <>
                    <Fait label="Capacité" valeur={e.salle.capacite ? `${e.salle.capacite} pers.` : "—"} />
                    <Fait label="Insonorisé" valeur={e.salle.isolation_phonique ? "Oui" : "Non"} />
                  </>
                )}
                {e.terrain && (
                  <>
                    <Fait label="Pelouse" valeur={e.terrain.type_pelouse ?? "—"} />
                    <Fait label="Éclairage" valeur={e.terrain.eclairage ? "Oui" : "Non"} />
                    <Fait label="Dimensions" valeur={e.terrain.dimensions ?? "—"} />
                  </>
                )}
              </div>

              {e.position ? (
                <Itineraire dest={e.position} nom={e.nom} />
              ) : (
                <div style={{ marginTop: 18, fontSize: 12, color: "#94A3B8" }}>
                  <span style={{ fontWeight: 700, color: "#BD3100" }}>Localisation</span> non renseignée par le gérant.
                </div>
              )}
            </div>
          )}

          {tab === "equip" && (
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10 }}>
              {(e.equipements ?? []).length === 0 && <div style={{ fontSize: 13, color: "#94A3B8" }}>Non renseigné.</div>}
              {(e.equipements ?? []).map((a) => (
                <div key={a} style={{ display: "flex", alignItems: "center", gap: 8, padding: 12, background: "#F8FAFC", borderRadius: 12 }}>
                  <div style={{ width: 28, height: 28, background: "#FFF0E6", borderRadius: 8, display: "flex", alignItems: "center", justifyContent: "center" }}>
                    <Icon.Wifi />
                  </div>
                  <span style={{ fontSize: 12, fontWeight: 500, color: "#334155" }}>{a}</span>
                </div>
              ))}
            </div>
          )}

          {tab === "avis" && (
            <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
              {(e.avis ?? []).length === 0 && <div style={{ fontSize: 13, color: "#94A3B8" }}>Pas encore d'avis.</div>}
              {(e.avis ?? []).map((a) => (
                <div key={a.id} style={{ background: "#F8FAFC", borderRadius: 12, padding: 14 }}>
                  <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 4 }}>
                    <span style={{ fontSize: 13, fontWeight: 600, color: "#BD3100" }}>{a.auteur ?? "Client"}</span>
                    <span style={{ display: "flex", gap: 2 }}>
                      {Array.from({ length: 5 }, (_, i) => <Icon.Star key={i} filled={i < a.note} />)}
                    </span>
                  </div>
                  {a.commentaire && <div style={{ fontSize: 12, color: "#64748B", lineHeight: 1.6 }}>{a.commentaire}</div>}
                </div>
              ))}
            </div>
          )}
        </div>

        <div style={{ position: "sticky", bottom: 0, padding: "14px 20px calc(14px + env(safe-area-inset-bottom))", background: "white", borderTop: "1px solid #F1F5F9", marginTop: "auto" }}>
          <PrimaryBtn
            label={peutReserver ? "Réserver maintenant" : "Connectez-vous en tant que client"}
            disabled={!peutReserver}
            onClick={() => navigate(`/espaces/${e.id}/reserver`)}
          />
        </div>
      </div>
    </div>
  );
}

function Fait({ label, valeur }: { label: string; valeur: string }) {
  return (
    <div style={{ padding: 12, background: "#F8FAFC", borderRadius: 12 }}>
      <div style={{ fontSize: 11, color: "#94A3B8", marginBottom: 2 }}>{label}</div>
      <div style={{ fontSize: 13, fontWeight: 600, color: "#334155" }}>{valeur}</div>
    </div>
  );
}

function FullScreen({ children }: { children: React.ReactNode }) {
  return (
    <div style={{ minHeight: "100dvh", background: "white", display: "flex", justifyContent: "center" }}>
      <div style={{ width: "100%", maxWidth: 440 }}>{children}</div>
    </div>
  );
}

export { fcfa };
