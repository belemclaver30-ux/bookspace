import { useEffect, useState } from "react";
import { AdminCard, AdminLayout } from "../../components/AdminLayout";
import { api, ApiError } from "../../lib/api";
import { ErrorNote, Spinner } from "../../components/ui";
import { useApi } from "../../lib/useApi";

interface Parametre {
  cle: string;
  valeur: string | null;
  libelle: string | null;
}

export default function AdminParametres() {
  const { data, loading, error, reload } = useApi<Parametre[]>("/admin/parametres");
  const [valeurs, setValeurs] = useState<Record<string, string>>({});
  const [busy, setBusy] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [err, setErr] = useState("");

  useEffect(() => {
    if (data) setValeurs(Object.fromEntries(data.map((p) => [p.cle, p.valeur ?? ""])));
  }, [data]);

  async function enregistrer(cle: string) {
    setBusy(cle);
    setErr("");
    setOk(null);
    try {
      await api.put(`/admin/parametres/${cle}`, { valeur: valeurs[cle] });
      setOk(cle);
      reload();
    } catch (e) {
      setErr(e instanceof ApiError ? e.premierMessage : "Erreur.");
    } finally {
      setBusy(null);
    }
  }

  return (
    <AdminLayout>
      <h1 style={{ fontFamily: "Poppins, sans-serif", fontSize: 20, color: "#BD3100", marginTop: 0 }}>Paramètres de la plateforme</h1>
      {loading && <Spinner />}
      <ErrorNote message={error || err} />
      {data?.map((p) => (
        <AdminCard key={p.cle}>
          <div style={{ fontSize: 13, fontWeight: 700, color: "#BD3100" }}>{p.libelle ?? p.cle}</div>
          <div style={{ fontSize: 11, color: "#94A3B8", marginBottom: 10 }}>{p.cle}</div>
          <div style={{ display: "flex", gap: 10, alignItems: "center" }}>
            <input
              value={valeurs[p.cle] ?? ""}
              onChange={(e) => setValeurs((v) => ({ ...v, [p.cle]: e.target.value }))}
              style={{ flex: 1, maxWidth: 200, padding: "10px 12px", border: "1.5px solid #E2E8F0", borderRadius: 10, fontSize: 14 }}
            />
            <button
              onClick={() => enregistrer(p.cle)}
              disabled={busy === p.cle}
              style={{ background: "#FC4E00", color: "white", border: "none", borderRadius: 10, padding: "10px 16px", fontSize: 13, fontWeight: 600, cursor: "pointer" }}
            >
              {busy === p.cle ? "…" : "Enregistrer"}
            </button>
            {ok === p.cle && <span style={{ color: "#16A34A", fontSize: 13, fontWeight: 600 }}>✓ Enregistré</span>}
          </div>
        </AdminCard>
      ))}
    </AdminLayout>
  );
}
