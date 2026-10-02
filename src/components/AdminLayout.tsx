import type { ReactNode } from "react";
import { DashboardShell, Panel, type NavItem } from "./DashboardShell";
import { NavIcon } from "./Icons";

const ITEMS: NavItem[] = [
  { to: "/admin", label: "Tableau de bord", icon: <NavIcon.Dashboard />, end: true },
  { to: "/admin/validations", label: "Validations", icon: <NavIcon.Check /> },
  { to: "/admin/litiges", label: "Litiges", icon: <NavIcon.Alert /> },
  { to: "/admin/parametres", label: "Paramètres", icon: <NavIcon.Sliders /> },
];

/** Console d'administration — barre latérale. */
export function AdminLayout({ children }: { children: ReactNode }) {
  return (
    <DashboardShell titre="Administration" sousTitre="Administrateur" items={ITEMS}>
      {children}
    </DashboardShell>
  );
}

/** Conservé pour compatibilité avec les pages admin existantes. */
export const AdminCard = Panel;
