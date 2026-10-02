import { useEffect, useState } from "react";
import { AppLayout } from "../components/AppLayout";
import { Icon } from "../components/Icons";
import { NotificationBell } from "../components/NotificationBell";
import { SpaceCard } from "../components/SpaceCard";
import { EmptyState, ErrorNote, Spinner } from "../components/ui";
import { useApi } from "../lib/useApi";
import { useAuth } from "../context/AuthContext";
import { positionActuelle, type Coord } from "../lib/geo";
import type { Espace, Paginated } from "../lib/types";

type Filtre = "all" | "salle_repetition" | "terrain_foot";

export default function Home() {
  const { user } = useAuth();
  const [filtre, setFiltre] = useState<Filtre>("all");
  const [recherche, setRecherche] = useState("");
  const [q, setQ] = useState("");
  const [pres, setPres] = useState(false);
  const [moi, setMoi] = useState<Coord | null>(null);
  const [geoErr, setGeoErr] = useState("");

  async function togglePres() {
    if (pres) { setPres(false); return; }
    setGeoErr("");
    try {
      const pos = moi ?? (await positionActuelle());
      setMoi(pos);
      setPres(true);
    } catch (e) {
      setGeoErr((e as Error).message);
    }
  }

  const [page, setPage] = useState(1);

  // Toute nouvelle recherche repart de la première page.
  useEffect(() => { setPage(1); }, [filtre, q, pres, moi]);

  const { data, loading, error } = useApi<Paginated<Espace>>("/espaces", {
    type: filtre === "all" ? undefined : filtre,
    q: q || undefined,
    lat: moi?.lat,
    lng: moi?.lng,
    tri: pres ? "proche" : undefined,
    page,
  });

  // On accumule les pages : la page 1 remplace, les suivantes s'ajoutent.
  const [espaces, setEspaces] = useState<Espace[]>([]);
  useEffect(() => {
    if (!data) return;
    const courante = data.meta?.current_page ?? 1;
    setEspaces((prev) => (courante === 1 ? data.data : [...prev, ...data.data]));
  }, [data]);

  const total = data?.meta?.total ?? espaces.length;
  const reste = data?.meta ? data.meta.current_page < data.meta.last_page : false;
  const chargementInitial = loading && page === 1;

  return (
    <AppLayout>
      <div style={{ background: "#BD3100", paddingBottom: 20 }}>
        <div style={{ padding: "20px 20px 0" }}>
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 16 }}>
            <div>
              <div style={{ fontSize: 12, color: "rgba(255,255,255,0.55)", fontWeight: 500 }}>
                Bonjour, {user?.name.split(" ")[0] ?? ""} 👋
              </div>
              <div style={{ fontFamily: "Poppins, sans-serif", fontSize: 20, fontWeight: 700, color: "white", marginTop: 2 }}>
                Trouvez votre espace
              </div>
            </div>
            <NotificationBell />
          </div>
          <form
            onSubmit={(e) => { e.preventDefault(); setQ(recherche.trim()); }}
            style={{ display: "flex", alignItems: "center", gap: 10, background: "white", borderRadius: 14, padding: "12px 14px" }}
          >
            <Icon.Search />
            <input
              placeholder="Chercher un espace..."
              value={recherche}
              onChange={(e) => setRecherche(e.target.value)}
              style={{ flex: 1, border: "none", outline: "none", fontSize: 14, color: "#1E293B", fontFamily: "inherit", background: "transparent" }}
            />
          </form>
        </div>
      </div>

      <div style={{ padding: "16px 20px 8px", background: "white", borderBottom: "1px solid #F1F5F9" }}>
        <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
          {([
            { id: "all", label: "Tous", icon: null },
            { id: "salle_repetition", label: "Salle de répétition", icon: <Icon.Music /> },
            { id: "terrain_foot", label: "Terrain de football", icon: <Icon.Football /> },
          ] as const).map((f) => (
            <button
              key={f.id}
              onClick={() => setFiltre(f.id)}
              style={{
                display: "flex", alignItems: "center", gap: 5, padding: "7px 13px", borderRadius: 20,
                border: filtre === f.id ? "none" : "1.5px solid #E2E8F0",
                background: filtre === f.id ? "#FC4E00" : "white",
                color: filtre === f.id ? "white" : "#475569",
                fontSize: 12, fontWeight: 600, cursor: "pointer", whiteSpace: "nowrap",
              }}
            >
              {f.icon}{f.label}
            </button>
          ))}
          <button
            onClick={togglePres}
            style={{
              display: "flex", alignItems: "center", gap: 5, padding: "7px 13px", borderRadius: 20,
              border: pres ? "none" : "1.5px solid #E2E8F0",
              background: pres ? "#FC4E00" : "white",
              color: pres ? "white" : "#475569",
              fontSize: 12, fontWeight: 600, cursor: "pointer", whiteSpace: "nowrap",
            }}
          >
            <Icon.Location />Près de moi
          </button>
        </div>
        {geoErr && <div style={{ fontSize: 11, color: "#DC2626", marginTop: 6 }}>{geoErr}</div>}
      </div>

      <div style={{ flex: 1, overflowY: "auto", padding: "16px 20px 24px" }}>
        {chargementInitial && <Spinner label="Chargement des espaces…" />}
        <ErrorNote message={error} />
        {!chargementInitial && !error && (
          <>
            <div style={{ fontSize: 12, fontWeight: 600, color: "#94A3B8", letterSpacing: 0.5, marginBottom: 12, textTransform: "uppercase" }}>
              {total} espace{total > 1 ? "s" : ""} trouvé{total > 1 ? "s" : ""}
            </div>
            {espaces.length === 0
              ? <EmptyState title="Aucun espace" subtitle="Essayez d'élargir votre recherche." />
              : espaces.map((e) => <SpaceCard key={e.id} espace={e} />)}

            {reste && (
              <button
                onClick={() => setPage((p) => p + 1)}
                disabled={loading}
                style={{
                  width: "100%", marginTop: 8, padding: "12px 16px", borderRadius: 12,
                  border: "1.5px solid #E2E8F0", background: "white", color: "#FC4E00",
                  fontSize: 13, fontWeight: 700, cursor: loading ? "default" : "pointer",
                }}
              >
                {loading ? "Chargement…" : "Voir plus d'espaces"}
              </button>
            )}
          </>
        )}
      </div>
    </AppLayout>
  );
}
