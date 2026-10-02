import { Link } from "react-router-dom";
import { Icon } from "./Icons";
import { fcfa } from "../lib/format";
import { formatDistance } from "../lib/geo";
import type { Espace } from "../lib/types";

const PLACEHOLDER = "https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=800&h=480&fit=crop&auto=format";

export function SpaceCard({ espace }: { espace: Espace }) {
  const img = espace.photo_principale ?? espace.photos?.[0] ?? PLACEHOLDER;
  const musique = espace.type === "salle_repetition";

  return (
    <Link
      to={`/espaces/${espace.id}`}
      style={{
        display: "block", background: "white", borderRadius: 16, overflow: "hidden",
        textDecoration: "none", boxShadow: "0 2px 12px rgba(120,40,15,0.07)", marginBottom: 14,
      }}
    >
      <div style={{ position: "relative", height: 160 }}>
        <img src={img} alt={espace.nom} style={{ width: "100%", height: "100%", objectFit: "cover" }} />
        <span
          style={{
            position: "absolute", top: 10, left: 10, background: musique ? "#FC4E00" : "#BD3100",
            color: "white", fontSize: 11, fontWeight: 600, padding: "4px 9px", borderRadius: 20,
            display: "flex", alignItems: "center", gap: 4,
          }}
        >
          {musique ? <Icon.Music /> : <Icon.Football />}
          {musique ? "Salle de répét." : "Terrain football"}
        </span>
      </div>
      <div style={{ padding: "12px 14px" }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start" }}>
          <div>
            <div style={{ fontSize: 15, fontWeight: 700, color: "#BD3100", marginBottom: 3 }}>{espace.nom}</div>
            <div style={{ display: "flex", alignItems: "center", gap: 4, color: "#64748B", fontSize: 12 }}>
              <Icon.Location />{espace.ville}
            </div>
            {typeof espace.distance_km === "number" && (
              <div style={{ fontSize: 11, fontWeight: 700, color: "#FC4E00", marginTop: 3 }}>
                à {formatDistance(espace.distance_km)}
              </div>
            )}
          </div>
          <div style={{ textAlign: "right" }}>
            <div style={{ fontSize: 15, fontWeight: 700, color: "#FC4E00" }}>
              {espace.tarif_horaire.toLocaleString("fr-FR")}{" "}
              <span style={{ fontSize: 11, fontWeight: 500, color: "#94A3B8" }}>FCFA/h</span>
            </div>
            <div style={{ display: "flex", alignItems: "center", gap: 3, justifyContent: "flex-end", marginTop: 2 }}>
              <Icon.Star filled />
              <span style={{ fontSize: 12, fontWeight: 600, color: "#374151" }}>{espace.note_moyenne.toFixed(1)}</span>
              <span style={{ fontSize: 11, color: "#94A3B8" }}>({espace.nb_avis})</span>
            </div>
          </div>
        </div>
      </div>
    </Link>
  );
}

export { fcfa };
