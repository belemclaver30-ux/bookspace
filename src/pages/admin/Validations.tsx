import { useState } from "react";
import { AdminCard, AdminLayout } from "../../components/AdminLayout";
import { api, ApiError } from "../../lib/api";
import { EmptyState, ErrorNote, Spinner } from "../../components/ui";
import { fcfa, LABEL_TYPE } from "../../lib/format";
import { useApi } from "../../lib/useApi";
import type { Espace, Paginated } from "../../lib/types";

interface GerantEnAttente {
  id: number;
  raison_sociale: string;
  ville: string | null;
  telephone_pro: string | null;
  user: { name: string; telephone: string; email: string | null };
}

export default function AdminValidations() {
  const [onglet, setOnglet] = useState<"gerants" | "espaces">("gerants");

  return (
    <AdminLayout>
      <h1 style={{ fontFamily: "Poppins, sans-serif", fontSize: 20, color: "#BD3100", marginTop: 0 }}>Validations</h1>
      <div style={{ display: "flex", gap: 8, marginBottom: 16 }}>
        {(["gerants", "espaces"] as const).map((o) => (
          <button
            key={o}
            onClick={() => setOnglet(o)}
            style={{
              padding: "8px 16px", borderRadius: 8, border: "none", cursor: "pointer", fontSize: 13, fontWeight: 600,
              background: onglet === o ? "#FC4E00" : "white", color: onglet === o ? "white" : "#475569",
            }}
          >
            {o === "gerants" ? "Gérants" : "Espaces"}
          </button>
        ))}
      </div>
      {onglet === "gerants" ? <Gerants /> : <Espaces />}
    </AdminLayout>
  );
}

function Gerants() {
  const { data, loading, error, reload } = useApi<Paginated<GerantEnAttente>>("/admin/gerants/en-attente");
  const [busy, setBusy] = useState<number | null>(null);
  const [err, setErr] = useState("");

  async function action(id: number, valider: boolean) {
    setBusy(id);
    setErr("");
    try {
      if (valider) await api.post(`/admin/gerants/${id}/valider`);
      else {
        const motif = prompt("Motif du rejet :");
        if (!motif) return;
        await api.post(`/admin/gerants/${id}/rejeter`, { motif });
      }
      reload();
    } catch (e) {
      setErr(e instanceof ApiError ? e.premierMessage : "Erreur.");
    } finally {
      setBusy(null);
    }
  }

  if (loading) return <Spinner />;
  return (
    <>
      <ErrorNote message={error || err} />
      {(data?.data.length ?? 0) === 0 && <EmptyState title="Aucun gérant en attente" />}
      {data?.data.map((g) => (
        <AdminCard key={g.id}>
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", flexWrap: "wrap", gap: 12 }}>
            <div>
              <div style={{ fontSize: 15, fontWeight: 700, color: "#BD3100" }}>{g.raison_sociale}</div>
              <div style={{ fontSize: 13, color: "#64748B" }}>
                {g.user.name} · {g.user.telephone}{g.user.email ? ` · ${g.user.email}` : ""}
                {g.ville ? ` · ${g.ville}` : ""}
              </div>
            </div>
            <div style={{ display: "flex", gap: 8 }}>
              <button onClick={() => action(g.id, true)} disabled={busy === g.id} style={btn("#16A34A")}>Valider</button>
              <button onClick={() => action(g.id, false)} disabled={busy === g.id} style={btn("#DC2626")}>Rejeter</button>
            </div>
          </div>
        </AdminCard>
      ))}
    </>
  );
}

function Espaces() {
  const { data, loading, error, reload } = useApi<Paginated<Espace> & { data: Espace[] }>("/admin/espaces/en-attente");
  const [busy, setBusy] = useState<number | null>(null);
  const [err, setErr] = useState("");

  async function action(id: number, valider: boolean) {
    setBusy(id);
    setErr("");
    try {
      if (valider) await api.post(`/admin/espaces/${id}/valider`);
      else {
        const motif = prompt("Motif du rejet :");
        if (!motif) return;
        await api.post(`/admin/espaces/${id}/rejeter`, { motif });
      }
      reload();
    } catch (e) {
      setErr(e instanceof ApiError ? e.premierMessage : "Erreur.");
    } finally {
      setBusy(null);
    }
  }

  if (loading) return <Spinner />;
  return (
    <>
      <ErrorNote message={error || err} />
      {(data?.data.length ?? 0) === 0 && <EmptyState title="Aucun espace en attente" />}
      {data?.data.map((e) => (
        <AdminCard key={e.id}>
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", flexWrap: "wrap", gap: 12 }}>
            <div>
              <div style={{ fontSize: 15, fontWeight: 700, color: "#BD3100" }}>{e.nom}</div>
              <div style={{ fontSize: 13, color: "#64748B" }}>
                {LABEL_TYPE[e.type]} · {e.adresse}, {e.ville} · {fcfa(e.tarif_horaire)}/h
                {e.gerant ? ` · ${e.gerant.raison_sociale}` : ""}
              </div>
              {e.description && <div style={{ fontSize: 13, color: "#94A3B8", marginTop: 4 }}>{e.description}</div>}
            </div>
            <div style={{ display: "flex", gap: 8 }}>
              <button onClick={() => action(e.id, true)} disabled={busy === e.id} style={btn("#16A34A")}>Valider</button>
              <button onClick={() => action(e.id, false)} disabled={busy === e.id} style={btn("#DC2626")}>Rejeter</button>
            </div>
          </div>
        </AdminCard>
      ))}
    </>
  );
}

function btn(color: string): React.CSSProperties {
  return { background: color, color: "white", border: "none", borderRadius: 8, padding: "8px 14px", fontSize: 13, fontWeight: 600, cursor: "pointer" };
}
