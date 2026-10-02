import { api } from "../lib/api";
import { BackButton, EmptyState, ErrorNote, Spinner } from "../components/ui";
import { dateLongue, heure } from "../lib/format";
import { useApi } from "../lib/useApi";
import type { NotificationItem, Paginated } from "../lib/types";

export default function Notifications() {
  const { data, loading, error, reload } = useApi<Paginated<NotificationItem>>("/notifications");
  const items = data?.data ?? [];
  const nonLues = items.filter((n) => !n.lu_at).length;

  async function toutLire() {
    await api.post("/notifications/tout-lu");
    reload();
  }

  return (
    <div style={{ minHeight: "100dvh", background: "#F8FAFC", display: "flex", justifyContent: "center" }}>
      <div style={{ width: "100%", maxWidth: 440, display: "flex", flexDirection: "column" }}>
        <div style={{ background: "white", borderBottom: "1px solid #F1F5F9", padding: "20px 20px 16px", display: "flex", alignItems: "center", gap: 12 }}>
          <BackButton to={-1} />
          <div style={{ fontFamily: "Poppins, sans-serif", fontSize: 17, fontWeight: 700, color: "#BD3100", flex: 1 }}>Notifications</div>
          {nonLues > 0 && (
            <button onClick={toutLire} style={{ background: "none", border: "none", color: "#FC4E00", fontSize: 12, fontWeight: 600, cursor: "pointer" }}>
              Tout marquer lu
            </button>
          )}
        </div>

        <div style={{ flex: 1, padding: "12px 16px 24px" }}>
          {loading && <Spinner />}
          <ErrorNote message={error} />
          {!loading && items.length === 0 && <EmptyState title="Aucune notification" />}
          {items.map((n) => (
            <div
              key={n.id}
              onClick={() => { if (!n.lu_at) api.post(`/notifications/${n.id}/lu`).then(reload); }}
              style={{
                background: n.lu_at ? "white" : "#FFF4EC", borderRadius: 12, padding: 14, marginBottom: 10,
                boxShadow: "0 1px 4px rgba(120,40,15,0.05)", cursor: n.lu_at ? "default" : "pointer",
              }}
            >
              <div style={{ display: "flex", justifyContent: "space-between", gap: 8 }}>
                <span style={{ fontSize: 14, fontWeight: 700, color: "#BD3100" }}>{n.titre}</span>
                {!n.lu_at && <span style={{ width: 8, height: 8, borderRadius: "50%", background: "#FC4E00", flexShrink: 0, marginTop: 5 }} />}
              </div>
              <div style={{ fontSize: 13, color: "#64748B", lineHeight: 1.5, margin: "3px 0 6px" }}>{n.contenu}</div>
              <div style={{ fontSize: 11, color: "#94A3B8" }}>{dateLongue(n.created_at)} à {heure(n.created_at)}</div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
