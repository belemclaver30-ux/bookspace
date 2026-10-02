import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { GerantLayout } from "../../components/GerantLayout";
import { LocalisationEditor } from "../../components/LocalisationEditor";
import { PhotosEditor } from "../../components/PhotosEditor";
import { api, ApiError } from "../../lib/api";
import { ErrorNote, PrimaryBtn, Spinner } from "../../components/ui";
import { dateLongue, heure, LABEL_TYPE } from "../../lib/format";
import { useApi } from "../../lib/useApi";
import type { Espace } from "../../lib/types";

const JOURS = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];

interface Detail extends Espace {
  blocages?: { id: number; date_debut: string; date_fin: string; motif: string | null }[];
}

export default function GerantEspaceGestion() {
  const { id } = useParams();
  const { data, loading, error, reload } = useApi<{ data: Detail }>(`/gerant/espaces/${id}`);

  return (
    <GerantLayout>
      <Link to="/gerant/espaces" style={{ fontSize: 13, color: "#FC4E00", fontWeight: 600, textDecoration: "none" }}>← Mes espaces</Link>
      <h1 style={{ fontFamily: "Poppins, sans-serif", fontSize: 20, color: "#BD3100", margin: "8px 0 0" }}>
        {data?.data.nom ?? "…"}
      </h1>
      {loading && <Spinner />}
      <ErrorNote message={error} />
      {data && (
        <>
          <div style={{ fontSize: 13, color: "#64748B", margin: "6px 0 20px" }}>
            {LABEL_TYPE[data.data.type]} · {data.data.adresse}, {data.data.ville} · statut : <b>{data.data.statut}</b>
          </div>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(min(100%, 320px), 1fr))", gap: 16, alignItems: "start" }}>
            <PhotosEditor espace={data.data} onChange={reload} />
            <LocalisationEditor
              espaceId={data.data.id}
              position={data.data.position}
              adresse={data.data.adresse}
              onSaved={reload}
            />
            <Horaires espace={data.data} onChange={reload} />
            <Blocages espace={data.data} onChange={reload} />
          </div>
        </>
      )}
    </GerantLayout>
  );
}

function Horaires({ espace, onChange }: { espace: Detail; onChange: () => void }) {
  const [horaires, setHoraires] = useState<Record<number, { o: string; f: string; actif: boolean }>>({});
  const [busy, setBusy] = useState(false);
  const [msg, setMsg] = useState("");
  const [err, setErr] = useState("");

  useEffect(() => {
    const base: Record<number, { o: string; f: string; actif: boolean }> = {};
    for (let j = 0; j < 7; j++) base[j] = { o: "08:00", f: "20:00", actif: false };
    (espace.disponibilites ?? []).forEach((d) => {
      base[d.jour_semaine] = { o: d.heure_ouverture, f: d.heure_fermeture, actif: true };
    });
    setHoraires(base);
  }, [espace.disponibilites]);

  async function enregistrer() {
    setBusy(true);
    setErr("");
    setMsg("");
    try {
      const payload = Object.entries(horaires)
        .filter(([, v]) => v.actif)
        .map(([j, v]) => ({ jour_semaine: Number(j), heure_ouverture: v.o, heure_fermeture: v.f }));
      await api.post(`/gerant/espaces/${espace.id}/horaires`, { horaires: payload });
      setMsg("Horaires enregistrés.");
      onChange();
    } catch (e) {
      setErr(e instanceof ApiError ? e.premierMessage : "Erreur.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <div style={{ background: "white", borderRadius: 16, padding: 16, marginBottom: 16, boxShadow: "0 2px 10px rgba(120,40,15,0.06)" }}>
      <div style={{ fontSize: 14, fontWeight: 700, color: "#BD3100", marginBottom: 12 }}>Horaires d'ouverture</div>
      <ErrorNote message={err} />
      {JOURS.map((nom, j) => (
        <div key={j} style={{ display: "flex", alignItems: "center", gap: 8, marginBottom: 8 }}>
          <label style={{ display: "flex", alignItems: "center", gap: 6, width: 110, fontSize: 13, color: "#334155" }}>
            <input
              type="checkbox"
              checked={horaires[j]?.actif ?? false}
              onChange={(e) => setHoraires((h) => ({ ...h, [j]: { ...h[j], actif: e.target.checked } }))}
              style={{ accentColor: "#FC4E00" }}
            />
            {nom}
          </label>
          <input
            type="time"
            value={horaires[j]?.o ?? "08:00"}
            disabled={!horaires[j]?.actif}
            onChange={(e) => setHoraires((h) => ({ ...h, [j]: { ...h[j], o: e.target.value } }))}
            style={timeInput}
          />
          <span style={{ color: "#94A3B8" }}>–</span>
          <input
            type="time"
            value={horaires[j]?.f ?? "20:00"}
            disabled={!horaires[j]?.actif}
            onChange={(e) => setHoraires((h) => ({ ...h, [j]: { ...h[j], f: e.target.value } }))}
            style={timeInput}
          />
        </div>
      ))}
      <div style={{ height: 10 }} />
      <PrimaryBtn label={busy ? "…" : "Enregistrer les horaires"} disabled={busy} onClick={enregistrer} />
      {msg && <div style={{ color: "#16A34A", fontSize: 13, fontWeight: 600, marginTop: 8 }}>{msg}</div>}
    </div>
  );
}

function Blocages({ espace, onChange }: { espace: Detail; onChange: () => void }) {
  const [debut, setDebut] = useState("");
  const [fin, setFin] = useState("");
  const [motif, setMotif] = useState("");
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState("");

  async function ajouter() {
    setBusy(true);
    setErr("");
    try {
      await api.post(`/gerant/espaces/${espace.id}/blocages`, { date_debut: debut, date_fin: fin, motif: motif || undefined });
      setDebut(""); setFin(""); setMotif("");
      onChange();
    } catch (e) {
      setErr(e instanceof ApiError ? e.premierMessage : "Erreur.");
    } finally {
      setBusy(false);
    }
  }

  async function supprimer(bid: number) {
    await api.del(`/gerant/espaces/${espace.id}/blocages/${bid}`);
    onChange();
  }

  return (
    <div style={{ background: "white", borderRadius: 16, padding: 16, boxShadow: "0 2px 10px rgba(120,40,15,0.06)" }}>
      <div style={{ fontSize: 14, fontWeight: 700, color: "#BD3100", marginBottom: 12 }}>Plages bloquées</div>
      <ErrorNote message={err} />
      {(espace.blocages ?? []).length === 0 && <div style={{ fontSize: 13, color: "#94A3B8", marginBottom: 12 }}>Aucune plage bloquée.</div>}
      {(espace.blocages ?? []).map((b) => (
        <div key={b.id} style={{ display: "flex", justifyContent: "space-between", alignItems: "center", background: "#F8FAFC", borderRadius: 10, padding: "10px 12px", marginBottom: 8 }}>
          <div style={{ fontSize: 12, color: "#334155" }}>
            {dateLongue(b.date_debut)} {heure(b.date_debut)} → {heure(b.date_fin)}
            {b.motif ? ` · ${b.motif}` : ""}
          </div>
          <button onClick={() => supprimer(b.id)} style={{ background: "none", border: "none", color: "#DC2626", fontSize: 12, fontWeight: 600, cursor: "pointer" }}>
            Débloquer
          </button>
        </div>
      ))}
      <div style={{ display: "flex", flexDirection: "column", gap: 8, marginTop: 10 }}>
        <input type="datetime-local" value={debut} onChange={(e) => setDebut(e.target.value)} style={timeInput} />
        <input type="datetime-local" value={fin} onChange={(e) => setFin(e.target.value)} style={timeInput} />
        <input placeholder="Motif (optionnel)" value={motif} onChange={(e) => setMotif(e.target.value)} style={timeInput} />
        <PrimaryBtn label={busy ? "…" : "Bloquer cette plage"} disabled={busy || !debut || !fin} onClick={ajouter} />
      </div>
    </div>
  );
}

const timeInput: React.CSSProperties = {
  padding: "8px 10px", border: "1.5px solid #E2E8F0", borderRadius: 8, fontSize: 13, fontFamily: "inherit",
};
