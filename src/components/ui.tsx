import type { CSSProperties, ReactNode } from "react";
import { useNavigate } from "react-router-dom";
import { Icon } from "./Icons";

export function PrimaryBtn({
  label, onClick, type = "button", disabled, style,
}: { label: ReactNode; onClick?: () => void; type?: "button" | "submit"; disabled?: boolean; style?: CSSProperties }) {
  return (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled}
      style={{
        width: "100%", padding: "15px", background: disabled ? "#94A3B8" : "#FC4E00",
        color: "white", border: "none", borderRadius: 14, fontFamily: "inherit", fontSize: 15,
        fontWeight: 600, cursor: disabled ? "default" : "pointer", letterSpacing: 0.2, ...style,
      }}
    >
      {label}
    </button>
  );
}

export function OutlineBtn({ label, onClick }: { label: ReactNode; onClick?: () => void }) {
  return (
    <button
      type="button"
      onClick={onClick}
      style={{
        width: "100%", padding: "15px", background: "white", color: "#FC4E00",
        border: "1.5px solid #FC4E00", borderRadius: 14, fontFamily: "inherit",
        fontSize: 15, fontWeight: 600, cursor: "pointer",
      }}
    >
      {label}
    </button>
  );
}

export function Field({
  label, type = "text", placeholder, value, onChange, required,
}: {
  label: string; type?: string; placeholder?: string;
  value: string; onChange: (v: string) => void; required?: boolean;
}) {
  return (
    <label style={{ display: "block", marginBottom: 16 }}>
      <div style={{ fontSize: 12, fontWeight: 600, color: "#475569", marginBottom: 6, letterSpacing: 0.3 }}>{label}</div>
      <input
        type={type}
        placeholder={placeholder}
        value={value}
        required={required}
        onChange={(e) => onChange(e.target.value)}
        style={{
          width: "100%", padding: "13px 14px", border: "1.5px solid #E2E8F0", borderRadius: 12,
          fontFamily: "inherit", fontSize: 14, color: "#1E293B", outline: "none", background: "#FAFCFF",
        }}
      />
    </label>
  );
}

export function BackButton({ to }: { to?: string | number }) {
  const navigate = useNavigate();
  return (
    <button
      type="button"
      onClick={() => (typeof to === "string" ? navigate(to) : navigate((to as number) ?? -1))}
      style={{
        width: 38, height: 38, borderRadius: 10, background: "white", border: "none",
        cursor: "pointer", display: "flex", alignItems: "center", justifyContent: "center",
        boxShadow: "0 1px 4px rgba(120,40,15,0.08)",
      }}
    >
      <Icon.ArrowLeft />
    </button>
  );
}

export function StatusBadge({ statut }: { statut: string }) {
  const map: Record<string, { bg: string; text: string; label: string }> = {
    confirmee: { bg: "#DCFCE7", text: "#16A34A", label: "Confirmée" },
    en_attente: { bg: "#FEF0D6", text: "#EB9F04", label: "En attente" },
    annulee: { bg: "#FEE2E2", text: "#DC2626", label: "Annulée" },
    terminee: { bg: "#FFE7D6", text: "#BD3100", label: "Terminée" },
    valide: { bg: "#DCFCE7", text: "#16A34A", label: "Validé" },
    rejete: { bg: "#FEE2E2", text: "#DC2626", label: "Rejeté" },
    suspendu: { bg: "#FEF0D6", text: "#EB9F04", label: "Suspendu" },
  };
  const s = map[statut] ?? map.en_attente;
  return (
    <span style={{ background: s.bg, color: s.text, fontSize: 11, fontWeight: 600, padding: "3px 9px", borderRadius: 20 }}>
      {s.label}
    </span>
  );
}

export function Spinner({ label }: { label?: string }) {
  return (
    <div style={{ display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "center", gap: 12, padding: 40, color: "#64748B" }}>
      <div style={{ width: 28, height: 28, border: "3px solid #E2E8F0", borderTopColor: "#FC4E00", borderRadius: "50%", animation: "spin 0.8s linear infinite" }} />
      {label && <span style={{ fontSize: 13 }}>{label}</span>}
      <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
    </div>
  );
}

export function ErrorNote({ message }: { message: string }) {
  if (!message) return null;
  return (
    <div style={{ background: "#FEF2F2", color: "#DC2626", fontSize: 13, fontWeight: 500, padding: "10px 14px", borderRadius: 12, marginBottom: 14 }}>
      {message}
    </div>
  );
}

export function EmptyState({ title, subtitle }: { title: string; subtitle?: string }) {
  return (
    <div style={{ textAlign: "center", padding: "48px 24px", color: "#94A3B8" }}>
      <div style={{ fontSize: 15, fontWeight: 600, color: "#475569", marginBottom: 4 }}>{title}</div>
      {subtitle && <div style={{ fontSize: 13 }}>{subtitle}</div>}
    </div>
  );
}
