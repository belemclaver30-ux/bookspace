import { useMemo } from "react";
import { COULEUR_STATUT } from "./charts";
import type { Reservation } from "../lib/types";

const JOURS = ["Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim"];
const MOIS = [
  "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
  "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre",
];

export function ymd(d: Date): string {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}

/**
 * Calendrier mensuel des réservations du gérant.
 * Chaque jour porteur de réservations affiche des pastilles colorées par statut ;
 * cliquer un jour le sélectionne (filtre la liste), recliquer désélectionne.
 */
export function CalendrierMois({
  mois, reservations, selected, onSelect, onChangeMois,
}: {
  mois: Date;
  reservations: Reservation[];
  selected: string | null;
  onSelect: (jour: string | null) => void;
  onChangeMois: (d: Date) => void;
}) {
  const aujourdHui = ymd(new Date());

  // Regroupe les réservations par jour (YYYY-MM-DD).
  const parJour = useMemo(() => {
    const map = new Map<string, Reservation[]>();
    for (const r of reservations) {
      const cle = ymd(new Date(r.date_debut));
      const liste = map.get(cle) ?? [];
      liste.push(r);
      map.set(cle, liste);
    }
    return map;
  }, [reservations]);

  // 6 semaines à partir du lundi de la semaine contenant le 1er.
  const cellules = useMemo(() => {
    const premier = new Date(mois.getFullYear(), mois.getMonth(), 1);
    const decalage = (premier.getDay() + 6) % 7; // 0 = lundi
    const debut = new Date(premier);
    debut.setDate(premier.getDate() - decalage);
    return Array.from({ length: 42 }, (_, i) => {
      const d = new Date(debut);
      d.setDate(debut.getDate() + i);
      return d;
    });
  }, [mois]);

  const changer = (delta: number) => onChangeMois(new Date(mois.getFullYear(), mois.getMonth() + delta, 1));

  return (
    <div style={{ background: "white", borderRadius: 14, padding: 16, boxShadow: "0 1px 4px rgba(120,40,15,0.06)" }}>
      <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: 14 }}>
        <button onClick={() => changer(-1)} style={navBtn}>‹</button>
        <div style={{ fontFamily: "Poppins, sans-serif", fontWeight: 700, color: "#BD3100", fontSize: 15 }}>
          {MOIS[mois.getMonth()]} {mois.getFullYear()}
          <span style={{ fontSize: 12, fontWeight: 500, color: "#94A3B8", marginLeft: 8 }}>
            {reservations.length} réservation{reservations.length > 1 ? "s" : ""}
          </span>
        </div>
        <button onClick={() => changer(1)} style={navBtn}>›</button>
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "repeat(7, 1fr)", gap: 4 }}>
        {JOURS.map((j) => (
          <div key={j} style={{ textAlign: "center", fontSize: 10, fontWeight: 700, color: "#94A3B8", padding: "2px 0" }}>{j}</div>
        ))}

        {cellules.map((d, i) => {
          const cle = ymd(d);
          const dansLeMois = d.getMonth() === mois.getMonth();
          const resas = parJour.get(cle) ?? [];
          const estAujourdHui = cle === aujourdHui;
          const estSelectionne = cle === selected;

          return (
            <button
              key={i}
              onClick={() => resas.length > 0 && onSelect(estSelectionne ? null : cle)}
              style={{
                aspectRatio: "1 / 1", minHeight: 40, border: "none", borderRadius: 10, padding: 3,
                display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "flex-start",
                cursor: resas.length > 0 ? "pointer" : "default",
                background: estSelectionne ? "#FC4E00" : resas.length > 0 ? "#FFF0E6" : "transparent",
                opacity: dansLeMois ? 1 : 0.35,
                outline: estAujourdHui && !estSelectionne ? "1.5px solid #FC4E00" : "none",
                outlineOffset: -1.5,
              }}
            >
              <span
                style={{
                  fontSize: 12, fontWeight: estSelectionne || estAujourdHui ? 800 : 500,
                  color: estSelectionne ? "white" : dansLeMois ? "#334155" : "#94A3B8",
                }}
              >
                {d.getDate()}
              </span>
              {resas.length > 0 && (
                <div style={{ display: "flex", gap: 2, marginTop: 3, flexWrap: "wrap", justifyContent: "center" }}>
                  {resas.slice(0, 3).map((r, k) => (
                    <span
                      key={k}
                      style={{
                        width: 5, height: 5, borderRadius: "50%",
                        background: estSelectionne ? "white" : (COULEUR_STATUT[r.statut] ?? "#94A3B8"),
                      }}
                    />
                  ))}
                  {resas.length > 3 && (
                    <span style={{ fontSize: 8, fontWeight: 700, color: estSelectionne ? "white" : "#BD3100", lineHeight: "5px" }}>
                      +{resas.length - 3}
                    </span>
                  )}
                </div>
              )}
            </button>
          );
        })}
      </div>

      <div style={{ display: "flex", gap: 12, marginTop: 12, flexWrap: "wrap", fontSize: 10.5, color: "#64748B" }}>
        {[
          ["confirmee", "Confirmée"], ["terminee", "Terminée"],
          ["en_attente", "En attente"], ["annulee", "Annulée"],
        ].map(([k, label]) => (
          <span key={k} style={{ display: "flex", alignItems: "center", gap: 4 }}>
            <span style={{ width: 7, height: 7, borderRadius: "50%", background: COULEUR_STATUT[k] }} />
            {label}
          </span>
        ))}
      </div>
    </div>
  );
}

const navBtn: React.CSSProperties = {
  width: 30, height: 30, borderRadius: 8, border: "1.5px solid #E2E8F0", background: "white",
  cursor: "pointer", fontSize: 16, color: "#475569", lineHeight: 1,
};
