import { useEffect, useState, type ReactNode } from "react";
import { NavLink, useLocation, useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { NotificationBell } from "./NotificationBell";

export interface NavItem {
  to: string;
  label: string;
  icon: ReactNode;
  end?: boolean;
  /** Pastille (ex. nombre de demandes en attente). */
  badge?: number;
}

interface Props {
  titre: string;
  sousTitre?: string;
  items: NavItem[];
  children: ReactNode;
  /** Affiche la cloche de notifications dans la barre supérieure. */
  notifications?: boolean;
}

const LARGEUR = 248;

/**
 * Coque de back-office avec barre latérale fixe (desktop) / tiroir (mobile).
 * Partagée par les tableaux de bord gérant et administrateur.
 */
export function DashboardShell({ titre, sousTitre, items, children, notifications }: Props) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const { pathname } = useLocation();
  const [ouvert, setOuvert] = useState(false);

  // Referme le tiroir à chaque changement de page (mobile).
  useEffect(() => setOuvert(false), [pathname]);

  async function deconnexion() {
    await logout();
    navigate("/", { replace: true });
  }

  const sidebar = (
    <aside
      style={{
        width: LARGEUR, background: "#BD3100", color: "white", display: "flex", flexDirection: "column",
        flexShrink: 0, height: "100dvh", position: "sticky", top: 0,
      }}
    >
      <div style={{ padding: "22px 20px 18px", borderBottom: "1px solid rgba(255,255,255,0.08)" }}>
        <div style={{ fontFamily: "Poppins, sans-serif", fontWeight: 800, fontSize: 18 }}>BookSpace</div>
        <div style={{ fontSize: 12, color: "rgba(255,255,255,0.45)", marginTop: 2 }}>{titre}</div>
      </div>

      <nav style={{ flex: 1, padding: "14px 12px", display: "flex", flexDirection: "column", gap: 3, overflowY: "auto" }}>
        {items.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            end={item.end}
            style={({ isActive }) => ({
              display: "flex", alignItems: "center", gap: 12, padding: "11px 14px", borderRadius: 10,
              textDecoration: "none", fontSize: 14, fontWeight: 600,
              color: isActive ? "white" : "rgba(255,255,255,0.6)",
              background: isActive ? "#FC4E00" : "transparent",
            })}
          >
            <span style={{ display: "flex", width: 20, justifyContent: "center" }}>{item.icon}</span>
            <span style={{ flex: 1 }}>{item.label}</span>
            {item.badge ? (
              <span style={{ background: "#F3BB00", color: "#7A1E00", fontSize: 11, fontWeight: 800, borderRadius: 10, minWidth: 18, height: 18, padding: "0 5px", display: "flex", alignItems: "center", justifyContent: "center" }}>
                {item.badge}
              </span>
            ) : null}
          </NavLink>
        ))}
      </nav>

      <div style={{ padding: "14px 16px", borderTop: "1px solid rgba(255,255,255,0.08)" }}>
        <div style={{ fontSize: 13, fontWeight: 600 }}>{user?.name}</div>
        <div style={{ fontSize: 11, color: "rgba(255,255,255,0.45)", marginBottom: 10 }}>{sousTitre ?? user?.telephone}</div>
        <button
          onClick={deconnexion}
          style={{
            width: "100%", background: "rgba(255,255,255,0.08)", color: "white", border: "none",
            borderRadius: 8, padding: "9px 12px", fontSize: 13, fontWeight: 600, cursor: "pointer",
          }}
        >
          Déconnexion
        </button>
      </div>
    </aside>
  );

  return (
    <div style={{ minHeight: "100dvh", background: "#EEF2F7", display: "flex" }}>
      {/* Sidebar desktop */}
      <div className="ds-sidebar-desktop">{sidebar}</div>

      {/* Tiroir mobile */}
      {ouvert && (
        <div
          onClick={() => setOuvert(false)}
          style={{ position: "fixed", inset: 0, background: "rgba(0,0,0,0.45)", zIndex: 40 }}
        >
          <div onClick={(e) => e.stopPropagation()} style={{ position: "fixed", left: 0, top: 0, zIndex: 50 }}>
            {sidebar}
          </div>
        </div>
      )}

      <div style={{ flex: 1, minWidth: 0, display: "flex", flexDirection: "column" }}>
        <header
          style={{
            height: 58, background: "white", borderBottom: "1px solid #E2E8F0", display: "flex",
            alignItems: "center", gap: 12, padding: "0 20px", position: "sticky", top: 0, zIndex: 20,
          }}
        >
          <button
            className="ds-burger"
            onClick={() => setOuvert(true)}
            aria-label="Ouvrir le menu"
            style={{ background: "none", border: "none", cursor: "pointer", padding: 4, display: "none" }}
          >
            <svg width="24" height="24" fill="none" stroke="#BD3100" strokeWidth="2" strokeLinecap="round" viewBox="0 0 24 24">
              <path d="M3 6h18M3 12h18M3 18h18" />
            </svg>
          </button>
          <div style={{ fontFamily: "Poppins, sans-serif", fontWeight: 700, fontSize: 15, color: "#BD3100", flex: 1 }}>
            {items.find((i) => (i.end ? pathname === i.to : pathname.startsWith(i.to)))?.label ?? titre}
          </div>
          {notifications && (
            <div style={{ background: "#BD3100", borderRadius: 12 }}>
              <NotificationBell />
            </div>
          )}
        </header>

        <main className="ds-main" style={{ flex: 1, padding: 24, maxWidth: 1080, width: "100%", margin: "0 auto" }}>
          {children}
        </main>
      </div>

      <style>{`
        @media (max-width: 900px) {
          .ds-sidebar-desktop { display: none; }
          .ds-burger { display: block !important; }
          .ds-main { padding: 16px !important; }
        }
      `}</style>
    </div>
  );
}

export function Panel({ title, children, style }: { title?: string; children: ReactNode; style?: React.CSSProperties }) {
  return (
    <div style={{ background: "white", borderRadius: 14, padding: 20, boxShadow: "0 1px 4px rgba(120,40,15,0.06)", marginBottom: 16, ...style }}>
      {title && <div style={{ fontSize: 14, fontWeight: 700, color: "#BD3100", marginBottom: 14 }}>{title}</div>}
      {children}
    </div>
  );
}
