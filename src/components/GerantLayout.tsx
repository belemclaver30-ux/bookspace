import type { ReactNode } from "react";
import { DashboardShell, Panel, type NavItem } from "./DashboardShell";
import { NavIcon } from "./Icons";
import { useApi } from "../lib/useApi";
import { useAuth } from "../context/AuthContext";
import type { Paginated, DemandeModification } from "../lib/types";

/** Espace gérant — barre latérale. */
export function GerantLayout({ children }: { children: ReactNode }) {
  const { user } = useAuth();
  const { data } = useApi<Paginated<DemandeModification>>("/gerant/demandes", { statut: "en_attente" });
  const enAttente = data?.data.length ?? 0;

  const items: NavItem[] = [
    { to: "/gerant", label: "Tableau de bord", icon: <NavIcon.Dashboard />, end: true },
    { to: "/gerant/reservations", label: "Réservations", icon: <NavIcon.Calendar /> },
    { to: "/gerant/demandes", label: "Demandes", icon: <NavIcon.Alert />, badge: enAttente || undefined },
    { to: "/gerant/espaces", label: "Mes espaces", icon: <NavIcon.Building /> },
    { to: "/profil", label: "Profil", icon: <NavIcon.User /> },
  ];

  return (
    <DashboardShell
      titre="Espace gérant"
      sousTitre={user?.gerant?.raison_sociale ?? undefined}
      items={items}
      notifications
    >
      {children}
    </DashboardShell>
  );
}

export { Panel };
