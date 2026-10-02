import { useState } from "react";
import { fcfa } from "../lib/format";

/*
 * Graphes SVG légers, sans dépendance, responsive (viewBox + width:100%).
 * Marques fines, extrémités arrondies 4px, axes discrets, étiquettes directes.
 * Palette : orange de marque pour la magnitude ; couleurs de statut (avec
 * légende) pour les répartitions.
 */

const INK = "#334155";
const MUTED = "#94A3B8";
const GRID = "#EEF2F7";
const BRAND = "#FC4E00";
const BRAND_SOFT = "rgba(252,78,0,0.12)";

// ─── Barres horizontales (magnitude) ─────────────────────────────────────────
export function BarsH({ data, format = fcfa }: { data: { label: string; value: number }[]; format?: (n: number) => string }) {
  const [hover, setHover] = useState<number | null>(null);
  if (data.length === 0) return <Vide />;

  const max = Math.max(...data.map((d) => d.value), 1);
  const rowH = 34;
  const h = data.length * rowH + 8;

  return (
    <div style={{ position: "relative", width: "100%" }}>
      <svg viewBox={`0 0 320 ${h}`} width="100%" height={h} role="img" preserveAspectRatio="xMinYMin meet">
        {data.map((d, i) => {
          const y = i * rowH + 4;
          const w = Math.max(2, (d.value / max) * 200);
          return (
            <g key={i} onMouseEnter={() => setHover(i)} onMouseLeave={() => setHover(null)} style={{ cursor: "default" }}>
              <rect x={0} y={y} width={320} height={rowH} fill={hover === i ? "#F8FAFC" : "transparent"} />
              <text x={0} y={y + 13} fontSize={11} fill={INK} fontWeight={600}>
                {d.label.length > 22 ? d.label.slice(0, 21) + "…" : d.label}
              </text>
              <rect x={0} y={y + 18} width={200} height={7} rx={3.5} fill={GRID} />
              <rect x={0} y={y + 18} width={w} height={7} rx={3.5} fill={BRAND} />
              <text x={208} y={y + 25} fontSize={10.5} fill={MUTED} fontWeight={600}>{format(d.value)}</text>
            </g>
          );
        })}
      </svg>
    </div>
  );
}

// ─── Aire / tendance (évolution dans le temps, une série) ─────────────────────
export function AreaTrend({
  data, format = fcfa, xLabel,
}: {
  data: { x: string; value: number }[];
  format?: (n: number) => string;
  xLabel?: (x: string) => string;
}) {
  const [hover, setHover] = useState<number | null>(null);
  if (data.length < 2) return <Vide />;

  const W = 320, H = 140, PAD_L = 6, PAD_R = 6, PAD_T = 10, PAD_B = 18;
  const max = Math.max(...data.map((d) => d.value), 1);
  const x = (i: number) => PAD_L + (i / (data.length - 1)) * (W - PAD_L - PAD_R);
  const y = (v: number) => PAD_T + (1 - v / max) * (H - PAD_T - PAD_B);

  const pts = data.map((d, i) => `${x(i)},${y(d.value)}`).join(" ");
  const aire = `${PAD_L},${H - PAD_B} ${pts} ${W - PAD_R},${H - PAD_B}`;

  const ticks = [0, Math.ceil((data.length - 1) / 2), data.length - 1];

  return (
    <div style={{ position: "relative", width: "100%" }}>
      <svg
        viewBox={`0 0 ${W} ${H}`}
        width="100%"
        height={H}
        preserveAspectRatio="xMinYMin meet"
        onMouseLeave={() => setHover(null)}
        onMouseMove={(e) => {
          const r = (e.currentTarget as SVGSVGElement).getBoundingClientRect();
          const px = ((e.clientX - r.left) / r.width) * W;
          const i = Math.round(((px - PAD_L) / (W - PAD_L - PAD_R)) * (data.length - 1));
          setHover(Math.max(0, Math.min(data.length - 1, i)));
        }}
        role="img"
      >
        {[0.5, 1].map((f) => (
          <line key={f} x1={PAD_L} x2={W - PAD_R} y1={y(max * f)} y2={y(max * f)} stroke={GRID} strokeWidth={1} />
        ))}
        <polygon points={aire} fill={BRAND_SOFT} />
        <polyline points={pts} fill="none" stroke={BRAND} strokeWidth={2} strokeLinejoin="round" strokeLinecap="round" />
        {hover !== null && (
          <>
            <line x1={x(hover)} x2={x(hover)} y1={PAD_T} y2={H - PAD_B} stroke={MUTED} strokeWidth={1} strokeDasharray="3 3" />
            <circle cx={x(hover)} cy={y(data[hover].value)} r={3.5} fill={BRAND} stroke="#fff" strokeWidth={2} />
          </>
        )}
        {ticks.map((i) => (
          <text key={i} x={x(i)} y={H - 5} fontSize={9.5} fill={MUTED} textAnchor={i === 0 ? "start" : i === data.length - 1 ? "end" : "middle"}>
            {xLabel ? xLabel(data[i].x) : data[i].x}
          </text>
        ))}
      </svg>
      {hover !== null && (
        <div style={{ position: "absolute", top: 0, left: `${(x(hover) / W) * 100}%`, transform: "translateX(-50%)", background: "#0F172A", color: "white", fontSize: 11, fontWeight: 600, padding: "4px 8px", borderRadius: 8, whiteSpace: "nowrap", pointerEvents: "none" }}>
          {xLabel ? xLabel(data[hover].x) : data[hover].x} · {format(data[hover].value)}
        </div>
      )}
    </div>
  );
}

// ─── Anneau (répartition en parts d'un tout) ─────────────────────────────────
function arc(cx: number, cy: number, r: number, a0: number, a1: number): string {
  const pt = (a: number) => [cx + r * Math.cos(a), cy + r * Math.sin(a)];
  const [x0, y0] = pt(a0);
  const [x1, y1] = pt(a1);
  const large = a1 - a0 > Math.PI ? 1 : 0;
  return `M ${x0} ${y0} A ${r} ${r} 0 ${large} 1 ${x1} ${y1}`;
}

export function Donut({ segments }: { segments: { label: string; value: number; color: string }[] }) {
  const [hover, setHover] = useState<number | null>(null);
  const total = segments.reduce((s, x) => s + x.value, 0);
  if (total === 0) return <Vide />;

  const R = 50, SW = 16, C = 60;
  const visibles = segments.filter((s) => s.value > 0);
  let a = -Math.PI / 2;

  return (
    <div style={{ display: "flex", alignItems: "center", gap: 20, flexWrap: "wrap" }}>
      <svg viewBox="0 0 120 120" width={120} height={120} role="img" style={{ flexShrink: 0 }}>
        <circle cx={C} cy={C} r={R} fill="none" stroke={GRID} strokeWidth={SW} />
        {visibles.map((s) => {
          const i = segments.indexOf(s);
          const sweep = (s.value / total) * Math.PI * 2;
          // Petit espace de 0.03 rad entre parts pour les séparer visuellement.
          const gap = visibles.length > 1 ? 0.03 : 0;
          const d = arc(C, C, R, a + gap / 2, a + sweep - gap / 2);
          a += sweep;
          return (
            <path
              key={i}
              d={d}
              fill="none"
              stroke={s.color}
              strokeWidth={hover === i ? SW + 3 : SW}
              strokeLinecap="butt"
              onMouseEnter={() => setHover(i)}
              onMouseLeave={() => setHover(null)}
              style={{ transition: "stroke-width 0.15s" }}
            />
          );
        })}
        <text x={C} y={C - 2} textAnchor="middle" fontSize={20} fontWeight={800} fill={INK}>{total}</text>
        <text x={C} y={C + 13} textAnchor="middle" fontSize={9} fill={MUTED}>total</text>
      </svg>
      <div style={{ display: "flex", flexDirection: "column", gap: 6, minWidth: 120 }}>
        {segments.map((s, i) => (
          <div
            key={i}
            onMouseEnter={() => setHover(i)}
            onMouseLeave={() => setHover(null)}
            style={{ display: "flex", alignItems: "center", gap: 8, fontSize: 12, opacity: hover === null || hover === i ? 1 : 0.5 }}
          >
            <span style={{ width: 10, height: 10, borderRadius: 3, background: s.color, flexShrink: 0 }} />
            <span style={{ color: INK, fontWeight: 600, flex: 1 }}>{s.label}</span>
            <span style={{ color: MUTED, fontWeight: 600 }}>{s.value}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

// ─── Jauge (une valeur en %) ─────────────────────────────────────────────────
export function Jauge({ pct, label }: { pct: number; label: string }) {
  const v = Math.max(0, Math.min(100, pct));
  const R = 46, C = 56, circ = Math.PI * R; // demi-cercle
  return (
    <div style={{ textAlign: "center" }}>
      <svg viewBox="0 0 112 70" width="100%" height={90} preserveAspectRatio="xMidYMin meet" style={{ maxWidth: 180 }}>
        <path d={`M ${C - R} ${C} A ${R} ${R} 0 0 1 ${C + R} ${C}`} fill="none" stroke={GRID} strokeWidth={12} strokeLinecap="round" />
        {v > 0 && (
          <path
            d={`M ${C - R} ${C} A ${R} ${R} 0 0 1 ${C + R} ${C}`}
            fill="none" stroke={BRAND} strokeWidth={12} strokeLinecap="butt"
            strokeDasharray={`${(v / 100) * circ} ${circ}`}
          />
        )}
        <text x={C} y={C - 6} textAnchor="middle" fontSize={20} fontWeight={800} fill={INK}>{v}%</text>
      </svg>
      <div style={{ fontSize: 12, color: MUTED, fontWeight: 600, marginTop: -6 }}>{label}</div>
    </div>
  );
}

function Vide() {
  return <div style={{ fontSize: 13, color: MUTED, padding: "24px 0", textAlign: "center" }}>Pas encore de données sur la période.</div>;
}

// Couleurs de statut réservées (accompagnées d'un libellé partout).
export const COULEUR_STATUT: Record<string, string> = {
  confirmee: "#16A34A",
  terminee: "#BD3100",
  en_attente: "#EB9F04",
  annulee: "#DC2626",
};
