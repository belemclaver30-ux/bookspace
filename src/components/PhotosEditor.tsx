import { useRef, useState } from "react";
import { ApiError, apiUpload, api } from "../lib/api";
import { ErrorNote } from "./ui";
import type { Espace } from "../lib/types";

/**
 * Gestion des photos réelles d'un espace (gérant) : ajout depuis la galerie
 * ou l'appareil photo, suppression, choix de la photo principale.
 */
export function PhotosEditor({ espace, onChange }: { espace: Espace; onChange: () => void }) {
  const input = useRef<HTMLInputElement>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const medias = espace.medias ?? [];

  async function ajouter(files: FileList | null) {
    if (!files || files.length === 0) return;
    setBusy(true);
    setError("");
    try {
      const form = new FormData();
      Array.from(files).slice(0, 10).forEach((f) => form.append("photos[]", f));
      await apiUpload(`/gerant/espaces/${espace.id}/photos`, form);
      onChange();
    } catch (e) {
      setError(e instanceof ApiError ? e.premierMessage : "Envoi impossible.");
    } finally {
      setBusy(false);
      if (input.current) input.current.value = "";
    }
  }

  async function supprimer(photoId: number) {
    if (!confirm("Supprimer cette photo ?")) return;
    setBusy(true);
    setError("");
    try {
      await api.del(`/gerant/espaces/${espace.id}/photos/${photoId}`);
      onChange();
    } catch (e) {
      setError(e instanceof ApiError ? e.premierMessage : "Suppression impossible.");
    } finally {
      setBusy(false);
    }
  }

  async function principale(photoId: number) {
    setBusy(true);
    setError("");
    try {
      await api.post(`/gerant/espaces/${espace.id}/photos/${photoId}/principale`);
      onChange();
    } catch (e) {
      setError(e instanceof ApiError ? e.premierMessage : "Action impossible.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <div style={{ background: "white", borderRadius: 16, padding: 16, boxShadow: "0 2px 10px rgba(120,40,15,0.06)" }}>
      <div style={{ fontSize: 14, fontWeight: 700, color: "#BD3100", marginBottom: 4 }}>Photos de l'espace</div>
      <div style={{ fontSize: 12, color: "#94A3B8", marginBottom: 12 }}>
        Ajoutez de vraies photos : elles rassurent les clients et augmentent les réservations.
      </div>

      <ErrorNote message={error} />

      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(min(100%, 110px), 1fr))", gap: 10, marginBottom: 12 }}>
        {medias.map((m, i) => (
          <div key={m.id} style={{ position: "relative", aspectRatio: "4 / 3", borderRadius: 10, overflow: "hidden", background: "#F1F5F9" }}>
            <img src={m.url} alt="" style={{ width: "100%", height: "100%", objectFit: "cover" }} />
            {i === 0 && (
              <span style={{ position: "absolute", top: 6, left: 6, background: "#FC4E00", color: "white", fontSize: 9, fontWeight: 700, padding: "2px 6px", borderRadius: 6 }}>
                Principale
              </span>
            )}
            <button
              onClick={() => supprimer(m.id)}
              disabled={busy}
              aria-label="Supprimer"
              style={{ position: "absolute", top: 4, right: 4, width: 22, height: 22, borderRadius: "50%", border: "none", background: "rgba(0,0,0,0.55)", color: "white", fontSize: 13, cursor: "pointer", lineHeight: 1 }}
            >
              ×
            </button>
            {i !== 0 && (
              <button
                onClick={() => principale(m.id)}
                disabled={busy}
                style={{ position: "absolute", bottom: 4, left: 4, right: 4, padding: "3px 0", border: "none", borderRadius: 6, background: "rgba(255,255,255,0.9)", color: "#BD3100", fontSize: 10, fontWeight: 700, cursor: "pointer" }}
              >
                Définir principale
              </button>
            )}
          </div>
        ))}

        <button
          onClick={() => input.current?.click()}
          disabled={busy}
          style={{
            aspectRatio: "4 / 3", borderRadius: 10, border: "2px dashed #E2E8F0", background: "#FFF8F3",
            color: "#BD3100", fontSize: 12, fontWeight: 600, cursor: "pointer", display: "flex",
            flexDirection: "column", alignItems: "center", justifyContent: "center", gap: 4,
          }}
        >
          <span style={{ fontSize: 22 }}>＋</span>
          {busy ? "Envoi…" : "Ajouter"}
        </button>
      </div>

      <input
        ref={input}
        type="file"
        accept="image/*"
        multiple
        capture="environment"
        onChange={(e) => ajouter(e.target.files)}
        style={{ display: "none" }}
      />
      <div style={{ fontSize: 11, color: "#94A3B8" }}>
        JPEG / PNG / WebP · 8 Mo max par photo · 10 photos max.
      </div>
    </div>
  );
}
