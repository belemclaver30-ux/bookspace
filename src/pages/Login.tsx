import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { ApiError } from "../lib/api";
import { BackButton, ErrorNote, Field, PrimaryBtn } from "../components/ui";
import { useAuth } from "../context/AuthContext";
import { accueilPour } from "../lib/routes";

export default function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [telephone, setTelephone] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError("");
    try {
      const u = await login(telephone.trim(), password);
      navigate(accueilPour(u.role), { replace: true });
    } catch (err) {
      setError(err instanceof ApiError ? err.premierMessage : "Connexion impossible.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <div style={{ minHeight: "100dvh", background: "white", display: "flex", justifyContent: "center" }}>
      <div style={{ width: "100%", maxWidth: 440, display: "flex", flexDirection: "column" }}>
        <div style={{ padding: "20px 24px 0" }}><BackButton to="/" /></div>
        <form onSubmit={submit} style={{ flex: 1, padding: "28px 24px 36px" }}>
          <div style={{ marginBottom: 32 }}>
            <div style={{ fontFamily: "Poppins, sans-serif", fontSize: 26, fontWeight: 700, color: "#BD3100", marginBottom: 6 }}>Bon retour 👋</div>
            <div style={{ fontSize: 14, color: "#64748B" }}>Connectez-vous pour continuer</div>
          </div>
          <ErrorNote message={error} />
          <Field label="NUMÉRO DE TÉLÉPHONE" placeholder="+226 70 00 00 00" value={telephone} onChange={setTelephone} required />
          <Field label="MOT DE PASSE" type="password" placeholder="••••••••" value={password} onChange={setPassword} required />
          <div style={{ height: 12 }} />
          <PrimaryBtn type="submit" label={busy ? "Connexion…" : "Se connecter"} disabled={busy} />
          <div style={{ textAlign: "center", marginTop: 28, fontSize: 13, color: "#64748B" }}>
            Pas encore de compte ?{" "}
            <Link to="/inscription" style={{ color: "#FC4E00", fontWeight: 600 }}>S'inscrire</Link>
          </div>
        </form>
      </div>
    </div>
  );
}
