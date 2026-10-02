import { useState } from "react";
import { Link } from "react-router-dom";
import { GerantLayout } from "../../components/GerantLayout";
import { api, ApiError } from "../../lib/api";
import { EmptyState, ErrorNote, Field, PrimaryBtn, Spinner, StatusBadge } from "../../components/ui";
import { fcfa, LABEL_TYPE } from "../../lib/format";
import { useApi } from "../../lib/useApi";
import { useAuth } from "../../context/AuthContext";
import type { Espace, TypeEspace } from "../../lib/types";

export default function GerantEspaces() {
  const { user } = useAuth();
  const { data, loading, error, reload } = useApi<{ data: Espace[] }>("/gerant/espaces");
  const [creation, setCreation] = useState(false);
  const valide = user?.gerant?.statut_validation === "valide";

  return (
    <GerantLayout>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", flexWrap: "wrap", gap: 12 }}>
        <h1 style={{ fontFamily: "Poppins, sans-serif", fontSize: 20, color: "#BD3100", margin: 0 }}>Mes espaces</h1>
        {valide && (
          <button
            onClick={() => setCreation((v) => !v)}
            style={{ background: "#FC4E00", color: "white", border: "none", borderRadius: 10, padding: "9px 16px", fontSize: 13, fontWeight: 600, cursor: "pointer" }}
          >
            {creation ? "Fermer" : "+ Ajouter un espace"}
          </button>
        )}
      </div>
      <div style={{ height: 16 }} />

      {!valide && (
        <div style={{ background: "#FEF9C3", color: "#854D0E", fontSize: 13, padding: 14, borderRadius: 12, marginBottom: 16 }}>
          Compte gérant non validé : la création d'espace sera disponible après validation.
        </div>
      )}

      {creation && <FormulaireEspace onCree={() => { setCreation(false); reload(); }} />}

      {loading && <Spinner />}
      <ErrorNote message={error} />
      {!loading && (data?.data.length ?? 0) === 0 && !creation && (
        <EmptyState title="Aucun espace" subtitle={valide ? "Ajoutez votre premier espace." : undefined} />
      )}

      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(min(100%, 280px), 1fr))", gap: 14 }}>
        {data?.data.map((e) => (
          <div key={e.id} style={{ background: "white", borderRadius: 14, padding: 16, boxShadow: "0 1px 4px rgba(120,40,15,0.06)" }}>
            <Link to={`/gerant/espaces/${e.id}`} style={{ textDecoration: "none" }}>
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", marginBottom: 6 }}>
                <div>
                  <div style={{ fontSize: 15, fontWeight: 700, color: "#BD3100" }}>{e.nom}</div>
                  <div style={{ fontSize: 12, color: "#94A3B8" }}>{LABEL_TYPE[e.type]} · {e.ville}</div>
                </div>
                <StatusBadge statut={e.statut} />
              </div>
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginTop: 8 }}>
                <span style={{ fontSize: 13, color: "#64748B" }}>{fcfa(e.tarif_horaire)}/h</span>
                <span style={{ fontSize: 12, color: "#FC4E00", fontWeight: 600 }}>Gérer →</span>
              </div>
            </Link>
            {e.statut === "rejete" && (
              <button
                onClick={() => api.post(`/gerant/espaces/${e.id}/soumettre`).then(reload)}
                style={{ marginTop: 10, background: "#FFF0E6", color: "#FC4E00", border: "none", borderRadius: 10, padding: "8px 12px", fontSize: 12, fontWeight: 600, cursor: "pointer" }}
              >
                Re-soumettre à validation
              </button>
            )}
          </div>
        ))}
      </div>
    </GerantLayout>
  );
}

function FormulaireEspace({ onCree }: { onCree: () => void }) {
  const [type, setType] = useState<TypeEspace>("salle_repetition");
  const [f, setF] = useState({ nom: "", adresse: "", ville: "Ouagadougou", tarif_horaire: "", description: "", equipements: "" });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const set = (k: keyof typeof f) => (v: string) => setF((s) => ({ ...s, [k]: v }));

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError("");
    try {
      await api.post("/gerant/espaces", {
        type,
        nom: f.nom.trim(),
        adresse: f.adresse.trim(),
        ville: f.ville.trim(),
        tarif_horaire: Number(f.tarif_horaire),
        description: f.description.trim() || undefined,
        equipements: f.equipements.split(",").map((x) => x.trim()).filter(Boolean),
      });
      onCree();
    } catch (err) {
      setError(err instanceof ApiError ? err.premierMessage : "Création impossible.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <form onSubmit={submit} style={{ background: "white", borderRadius: 14, padding: 20, marginBottom: 16, boxShadow: "0 1px 4px rgba(120,40,15,0.06)", maxWidth: 460 }}>
      <div style={{ fontSize: 14, fontWeight: 700, color: "#BD3100", marginBottom: 12 }}>Nouvel espace</div>
      <div style={{ display: "flex", gap: 8, marginBottom: 14 }}>
        {(["salle_repetition", "terrain_foot"] as const).map((t) => (
          <button
            key={t}
            type="button"
            onClick={() => setType(t)}
            style={{
              flex: 1, padding: 10, borderRadius: 10, fontSize: 12, fontWeight: 600, cursor: "pointer",
              border: type === t ? "2px solid #FC4E00" : "1.5px solid #E2E8F0",
              background: type === t ? "#FFF0E6" : "white", color: type === t ? "#FC4E00" : "#64748B",
            }}
          >
            {LABEL_TYPE[t]}
          </button>
        ))}
      </div>
      <ErrorNote message={error} />
      <Field label="NOM" value={f.nom} onChange={set("nom")} required />
      <Field label="ADRESSE" value={f.adresse} onChange={set("adresse")} required />
      <Field label="VILLE" value={f.ville} onChange={set("ville")} required />
      <Field label="TARIF HORAIRE (FCFA)" type="number" value={f.tarif_horaire} onChange={set("tarif_horaire")} required />
      <Field label="ÉQUIPEMENTS (séparés par des virgules)" value={f.equipements} onChange={set("equipements")} placeholder="WiFi, Climatisation, Parking" />
      <Field label="DESCRIPTION" value={f.description} onChange={set("description")} />
      <PrimaryBtn type="submit" label={busy ? "Création…" : "Créer (soumis à validation)"} disabled={busy} />
    </form>
  );
}
