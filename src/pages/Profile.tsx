import { useNavigate } from "react-router-dom";
import type { ReactNode } from "react";
import { AppLayout } from "../components/AppLayout";
import { GerantLayout } from "../components/GerantLayout";
import { Icon } from "../components/Icons";
import { OutlineBtn } from "../components/ui";
import { useAuth } from "../context/AuthContext";

export default function Profile() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const isManager = user?.role === "gerant";

  async function deconnexion() {
    await logout();
    navigate("/", { replace: true });
  }

  const contenu = (
    <>
      <div style={{ background: "#BD3100", padding: "24px 20px 28px", textAlign: "center", borderRadius: isManager ? 16 : 0, marginBottom: isManager ? 20 : 0 }}>
        <div style={{ width: 72, height: 72, borderRadius: "50%", background: "#FC4E00", display: "flex", alignItems: "center", justifyContent: "center", fontFamily: "Poppins, sans-serif", fontSize: 28, fontWeight: 700, color: "white", margin: "0 auto 12px" }}>
          {user?.name.charAt(0).toUpperCase()}
        </div>
        <div style={{ fontFamily: "Poppins, sans-serif", fontSize: 18, fontWeight: 700, color: "white" }}>{user?.name}</div>
        <div style={{ fontSize: 13, color: "rgba(255,255,255,0.5)", marginTop: 2 }}>{user?.telephone}</div>
        <div style={{ display: "inline-flex", alignItems: "center", gap: 5, marginTop: 8, background: isManager ? "rgba(252,78,0,0.4)" : "rgba(16,185,129,0.25)", borderRadius: 20, padding: "4px 12px" }}>
          <span style={{ fontSize: 11, color: isManager ? "#F3BB00" : "#6EE7B7", fontWeight: 600 }}>
            {isManager ? "Compte Gérant" : "Compte Client"}
          </span>
        </div>
        {isManager && user?.gerant && user.gerant.statut_validation !== "valide" && (
          <div style={{ marginTop: 12, fontSize: 12, color: "#FCD34D", fontWeight: 600 }}>
            Compte gérant : {user.gerant.statut_validation === "en_attente" ? "en attente de validation" : "rejeté"}
          </div>
        )}
      </div>

      <div style={{ padding: isManager ? 0 : "16px 20px 24px", maxWidth: 460 }}>
        <Group title="Préférences">
          <Item icon={<Icon.Bell />} label="Notifications" />
          <Item icon={<Icon.Settings />} label="Langue : Français" />
        </Group>
        <Group title="Aide & Support">
          <Item icon={<Icon.Settings />} label="Centre d'aide" />
          <Item icon={<Icon.Settings />} label="Conditions d'utilisation" />
        </Group>
        <div style={{ marginTop: 8 }}>
          <OutlineBtn label="Se déconnecter" onClick={deconnexion} />
        </div>
      </div>
    </>
  );

  return isManager ? <GerantLayout>{contenu}</GerantLayout> : <AppLayout>{contenu}</AppLayout>;
}

function Group({ title, children }: { title: string; children: ReactNode }) {
  return (
    <div style={{ marginBottom: 20 }}>
      <div style={{ fontSize: 11, fontWeight: 700, color: "#94A3B8", letterSpacing: 0.5, textTransform: "uppercase", marginBottom: 8, paddingLeft: 4 }}>{title}</div>
      <div style={{ background: "white", borderRadius: 16, overflow: "hidden", boxShadow: "0 1px 4px rgba(120,40,15,0.05)" }}>{children}</div>
    </div>
  );
}

function Item({ icon, label }: { icon: ReactNode; label: string }) {
  return (
    <div style={{ display: "flex", alignItems: "center", gap: 12, padding: "14px 16px", borderBottom: "1px solid #F8FAFC" }}>
      <div style={{ width: 32, height: 32, borderRadius: 8, background: "#F8FAFC", display: "flex", alignItems: "center", justifyContent: "center" }}>{icon}</div>
      <div style={{ flex: 1, fontSize: 14, color: "#1E293B", fontWeight: 500 }}>{label}</div>
      <Icon.ChevronRight />
    </div>
  );
}
