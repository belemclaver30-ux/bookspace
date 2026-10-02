import type { ReactNode } from "react";
import { Navigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { accueilPour } from "../lib/routes";
import { Spinner } from "./ui";

/** Écran plein pendant la vérification de session. */
function Loading() {
  return (
    <div style={{ minHeight: "100dvh", background: "#BD3100", display: "flex", alignItems: "center", justifyContent: "center" }}>
      <Spinner />
    </div>
  );
}

export function RequireAuth({ children, role }: { children: ReactNode; role?: "client" | "gerant" | "admin" }) {
  const { user, loading } = useAuth();

  if (loading) return <Loading />;
  if (!user) return <Navigate to="/connexion" replace />;
  if (role && user.role !== role) {
    return <Navigate to={accueilPour(user.role)} replace />;
  }
  return <>{children}</>;
}

export function RedirectIfAuth({ children }: { children: ReactNode }) {
  const { user, loading } = useAuth();
  if (loading) return <Loading />;
  if (user) return <Navigate to={accueilPour(user.role)} replace />;
  return <>{children}</>;
}
