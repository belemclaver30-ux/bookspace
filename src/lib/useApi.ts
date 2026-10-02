import { useCallback, useEffect, useState } from "react";
import { ApiError, apiFetch } from "./api";

interface State<T> {
  data: T | null;
  loading: boolean;
  error: string;
  reload: () => void;
}

/** Chargement d'une ressource GET avec états loading / error / reload. */
export function useApi<T>(path: string | null, params?: Record<string, string | number | undefined | null>): State<T> {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(Boolean(path));
  const [error, setError] = useState("");
  const [tick, setTick] = useState(0);

  const key = params ? JSON.stringify(params) : "";

  useEffect(() => {
    if (!path) {
      // Plus de ressource à charger : on évite d'afficher des données périmées.
      setData(null);
      setLoading(false);
      setError("");
      return;
    }
    let annule = false;
    setLoading(true);
    setError("");

    apiFetch<T>(path, { params: params ?? undefined })
      .then((res) => {
        if (!annule) setData(res);
      })
      .catch((e) => {
        if (!annule) setError(e instanceof ApiError ? e.premierMessage : "Erreur de connexion au serveur.");
      })
      .finally(() => {
        if (!annule) setLoading(false);
      });

    return () => {
      annule = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [path, key, tick]);

  const reload = useCallback(() => setTick((t) => t + 1), []);

  return { data, loading, error, reload };
}
