import { Link } from "react-router-dom";
import { useApi } from "../lib/useApi";
import type { NotificationItem, Paginated } from "../lib/types";

function BellIcon() {
  return (
    <svg width="20" height="20" fill="none" stroke="white" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
      <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
      <path d="M13.73 21a2 2 0 0 1-3.46 0" />
    </svg>
  );
}

/** Cloche de notifications avec pastille du nombre de non-lues. */
export function NotificationBell() {
  const { data } = useApi<Paginated<NotificationItem>>("/notifications");
  const nonLues = (data?.data ?? []).filter((n) => !n.lu_at).length;

  return (
    <Link
      to="/notifications"
      style={{
        position: "relative", width: 40, height: 40, borderRadius: 12, background: "rgba(255,255,255,0.1)",
        display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0,
      }}
    >
      <BellIcon />
      {nonLues > 0 && (
        <span
          style={{
            position: "absolute", top: -4, right: -4, minWidth: 18, height: 18, padding: "0 4px",
            borderRadius: 9, background: "#EF4444", color: "white", fontSize: 11, fontWeight: 700,
            display: "flex", alignItems: "center", justifyContent: "center",
          }}
        >
          {nonLues > 9 ? "9+" : nonLues}
        </span>
      )}
    </Link>
  );
}
