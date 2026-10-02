import type { Role } from "./types";

/** Écran d'accueil par défaut selon le rôle. */
export function accueilPour(role: Role | undefined): string {
  switch (role) {
    case "admin":
      return "/admin";
    case "gerant":
      return "/gerant";
    default:
      return "/app";
  }
}
