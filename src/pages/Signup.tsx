import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { ApiError } from "../lib/api";
import { BackButton, ErrorNote, Field, PrimaryBtn } from "../components/ui";
import { useAuth } from "../context/AuthContext";
import { accueilPour } from "../lib/routes";

export default function Signup() {
  const { register } = useAuth();
  const navigate = useNavigate();
  const [role, setRole] = useState<"client" | "gerant">("client");
  const [form, setForm] = useState({ name: "", telephone: "", email: "", password: "", raison_sociale: "" });
  const [accepte, setAccepte] = useState(false);
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  const set = (k: keyof typeof form) => (v: string) => setForm((f) => ({ ...f, [k]: v }));

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    if (!accepte) {
      setError("Vous devez accepter les conditions d'utilisation.");
      return;
    }
    setBusy(true);
    setError("");
    try {
      const u = await register({
        name: form.name.trim(),
        telephone: form.telephone.trim(),
        email: form.email.trim() || undefined,
        password: form.password,
        role,
        raison_sociale: role === "gerant" ? form.raison_sociale.trim() : undefined,
      });
      navigate(accueilPour(u.role), { replace: true });
    } catch (err) {
      setError(err instanceof ApiError ? err.premierMessage : "Inscription impossible.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <div style={{ minHeight: "100dvh", background: "white", display: "flex", justifyContent: "center" }}>
      <div style={{ width: "100%", maxWidth: 440, display: "flex", flexDirection: "column" }}>
        <div style={{ padding: "20px 24px 0" }}><BackButton to="/" /></div>
        <form onSubmit={submit} style={{ flex: 1, padding: "24px 24px 36px" }}>
          <div style={{ marginBottom: 24 }}>
            <div style={{ fontFamily: "Poppins, sans-serif", fontSize: 26, fontWeight: 700, color: "#BD3100", marginBottom: 6 }}>Créer un compte</div>
            <div style={{ fontSize: 14, color: "#64748B" }}>Rejoignez BookSpace dès aujourd'hui</div>
          </div>
          <ErrorNote message={error} />
          <div style={{ display: "flex", gap: 10, marginBottom: 16 }}>
            {(["client", "gerant"] as const).map((r) => (
              <button
                key={r}
                type="button"
                onClick={() => setRole(r)}
                style={{
                  flex: 1, padding: "12px", borderRadius: 12,
                  border: role === r ? "2px solid #FC4E00" : "1.5px solid #E2E8F0",
                  background: role === r ? "#FFF0E6" : "white",
                  color: role === r ? "#FC4E00" : "#64748B",
                  fontFamily: "inherit", fontSize: 13, fontWeight: 600, cursor: "pointer",
                }}
              >
                {r === "client" ? "Client" : "Gérant"}
              </button>
            ))}
          </div>
          <Field label="NOM COMPLET" placeholder="Konan Yao Marcel" value={form.name} onChange={set("name")} required />
          {role === "gerant" && (
            <Field label="RAISON SOCIALE" placeholder="Studio Harmonie" value={form.raison_sociale} onChange={set("raison_sociale")} required />
          )}
          <Field label="NUMÉRO DE TÉLÉPHONE" placeholder="+226 70 00 00 00" value={form.telephone} onChange={set("telephone")} required />
          <Field label="EMAIL (optionnel)" type="email" placeholder="email@exemple.com" value={form.email} onChange={set("email")} />
          <Field label="MOT DE PASSE" type="password" placeholder="Min. 8 caractères" value={form.password} onChange={set("password")} required />
          <label style={{ display: "flex", gap: 8, alignItems: "flex-start", margin: "8px 0 20px" }}>
            <input type="checkbox" checked={accepte} onChange={(e) => setAccepte(e.target.checked)} style={{ marginTop: 2, accentColor: "#FC4E00" }} />
            <span style={{ fontSize: 12, color: "#64748B", lineHeight: 1.5 }}>
              J'accepte les Conditions d'utilisation et la Politique de confidentialité
            </span>
          </label>
          <PrimaryBtn type="submit" label={busy ? "Création…" : "Créer mon compte"} disabled={busy} />
          <div style={{ textAlign: "center", marginTop: 20, fontSize: 13, color: "#64748B" }}>
            Déjà un compte ? <Link to="/connexion" style={{ color: "#FC4E00", fontWeight: 600 }}>Se connecter</Link>
          </div>
        </form>
      </div>
    </div>
  );
}
