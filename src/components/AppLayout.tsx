import type { ReactNode } from "react";
import { NavLink, useLocation } from "react-router-dom";
import { Icon } from "./Icons";
import { useAuth } from "../context/AuthContext";

/**
 * Coque de l'application cliente.
 * - Mobile : colonne plein écran + barre de navigation basse.
 * - Bureau (≥ 900 px) : barre latérale à gauche + colonne de contenu centrée.
 */
export function AppLayout({ children, bottomNav = true }: { children: ReactNode; bottomNav?: boolean }) {
  return (
    <div style={{ minHeight: "100dvh", background: "#BD3100", display: "flex", justifyContent: "center" }}>
      {bottomNav && <SideRail />}
      <div
        style={{
          width: "100%", maxWidth: 440, minHeight: "100dvh", background: "#F8FAFC",
          position: "relative", display: "flex", flexDirection: "column",
          boxShadow: "0 0 60px rgba(0,0,0,0.25)",
        }}
      >
        <div style={{ flex: 1, display: "flex", flexDirection: "column", paddingBottom: bottomNav ? 70 : 0 }} className="app-content">
          {children}
        </div>
        {bottomNav && <BottomNav />}
      </div>

      <style>{`
        @media (min-width: 900px) {
          .app-siderail { display: flex !important; }
          .app-bottomnav { display: none !important; }
          .app-content { padding-bottom: 0 !important; }
        }
      `}</style>
    </div>
  );
}

function useItems() {
  const { user } = useAuth();
  return [
    { to: "/app", icon: Icon.Home, label: "Accueil" },
    { to: "/reservations", icon: Icon.Calendar, label: "Réservations" },
    { to: "/notifications", icon: Icon.Bell, label: "Notifications", plain: true },
    { to: "/profil", icon: Icon.User, label: "Profil" },
  ].filter(() => user?.role === "client");
}

function SideRail() {
  const items = useItems();
  const { pathname } = useLocation();

  return (
    <aside
      className="app-siderail"
      style={{
        display: "none", width: 230, flexDirection: "column", background: "#BD3100", color: "white",
        padding: "24px 16px", position: "sticky", top: 0, height: "100dvh",
      }}
    >
      <div style={{ fontFamily: "Poppins, sans-serif", fontWeight: 800, fontSize: 19, padding: "0 8px 20px" }}>BookSpace</div>
      {items.map((item) => {
        const active = pathname.startsWith(item.to);
        const I = item.icon;
        return (
          <NavLink
            key={item.to}
            to={item.to}
            style={{
              display: "flex", alignItems: "center", gap: 12, padding: "11px 14px", borderRadius: 10,
              textDecoration: "none", fontSize: 14, fontWeight: 600, marginBottom: 3,
              color: active ? "white" : "rgba(255,255,255,0.6)",
              background: active ? "#FC4E00" : "transparent",
            }}
          >
            {"plain" in item && item.plain
              ? <BellRail active={active} />
              : <I active={active} />}
            {item.label}
          </NavLink>
        );
      })}
    </aside>
  );
}

function BellRail({ active }: { active?: boolean }) {
  return (
    <svg width="22" height="22" fill="none" stroke={active ? "white" : "#94A3B8"} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
      <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" /><path d="M13.73 21a2 2 0 0 1-3.46 0" />
    </svg>
  );
}

function BottomNav() {
  const { pathname } = useLocation();
  const items = [
    { to: "/app", icon: Icon.Home, label: "Accueil" },
    { to: "/reservations", icon: Icon.Calendar, label: "Réservations" },
    { to: "/profil", icon: Icon.User, label: "Profil" },
  ];

  return (
    <nav
      className="app-bottomnav"
      style={{
        position: "sticky", bottom: 0, left: 0, right: 0, height: 70, background: "white",
        borderTop: "1px solid #F1F5F9", display: "flex", alignItems: "center",
        justifyContent: "space-around", boxShadow: "0 -4px 20px rgba(120,40,15,0.06)",
      }}
    >
      {items.map((item) => {
        const active = pathname.startsWith(item.to);
        const I = item.icon;
        return (
          <NavLink key={item.to} to={item.to} style={{ flex: 1, display: "flex", flexDirection: "column", alignItems: "center", gap: 3, textDecoration: "none", padding: "8px 0" }}>
            <I active={active} />
            <span style={{ fontSize: 10, color: active ? "#FC4E00" : "#94A3B8", fontWeight: active ? 600 : 400 }}>{item.label}</span>
          </NavLink>
        );
      })}
    </nav>
  );
}
