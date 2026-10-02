import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from "react";
import { api, setToken, UNAUTHORIZED_EVENT } from "../lib/api";
import type { Utilisateur } from "../lib/types";

interface AuthState {
  user: Utilisateur | null;
  loading: boolean;
  login: (telephone: string, password: string) => Promise<Utilisateur>;
  register: (payload: RegisterPayload) => Promise<Utilisateur>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
}

export interface RegisterPayload {
  name: string;
  telephone: string;
  email?: string;
  password: string;
  role: "client" | "gerant";
  raison_sociale?: string;
}

const AuthContext = createContext<AuthState | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<Utilisateur | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    try {
      const { data } = await api.get<{ data: Utilisateur }>("/auth/me");
      setUser(data);
    } catch {
      setUser(null);
    }
  }, []);

  useEffect(() => {
    refresh().finally(() => setLoading(false));
  }, [refresh]);

  // Jeton rejeté par l'API (401) : on repasse en état déconnecté, les gardes
  // de route renvoient alors vers /connexion.
  useEffect(() => {
    const onUnauthorized = () => setUser(null);
    window.addEventListener(UNAUTHORIZED_EVENT, onUnauthorized);
    return () => window.removeEventListener(UNAUTHORIZED_EVENT, onUnauthorized);
  }, []);

  const login = useCallback(async (telephone: string, password: string) => {
    const res = await api.post<{ token: string; data: Utilisateur }>("/auth/login", { telephone, password });
    setToken(res.token);
    setUser(res.data);
    return res.data;
  }, []);

  const register = useCallback(async (payload: RegisterPayload) => {
    const res = await api.post<{ token: string; data: Utilisateur }>("/auth/register", payload);
    setToken(res.token);
    setUser(res.data);
    return res.data;
  }, []);

  const logout = useCallback(async () => {
    try {
      await api.post("/auth/logout");
    } catch {
      /* ignore */
    }
    setToken(null);
    setUser(null);
  }, []);

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout, refresh }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth doit être utilisé dans un AuthProvider");
  return ctx;
}
