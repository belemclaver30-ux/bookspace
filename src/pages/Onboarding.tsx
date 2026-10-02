import { useEffect, useRef, useState } from "react";
import { Link, Navigate, useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { accueilPour } from "../lib/routes";

const SLIDES = [
  {
    img: "https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=1200&q=80&auto=format&fit=crop",
    titre: "Réservez votre espace en quelques secondes",
    sous: "Studios de répétition et terrains de foot, à portée de clic.",
  },
  {
    img: "https://images.unsplash.com/photo-1459865264687-595d652de67e?w=1200&q=80&auto=format&fit=crop",
    titre: "Payez l'acompte par Mobile Money",
    sous: "Orange Money, Moov Money : votre créneau bloqué à l'instant.",
  },
  {
    img: "https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?w=1200&q=80&auto=format&fit=crop",
    titre: "Des disponibilités en temps réel",
    sous: "Zéro double réservation, zéro allers-retours pour rien.",
  },
];

export default function Onboarding() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [i, setI] = useState(0);
  const timer = useRef<number | undefined>(undefined);

  useEffect(() => {
    const mq = window.matchMedia?.("(prefers-reduced-motion: reduce)");
    if (mq?.matches) return;
    timer.current = window.setInterval(() => setI((v) => (v + 1) % SLIDES.length), 5000);
    return () => window.clearInterval(timer.current);
  }, []);

  // Précharge les images suivantes pour un fondu sans à-coup.
  useEffect(() => {
    SLIDES.forEach((s) => { const im = new Image(); im.src = s.img; });
  }, []);

  if (user) return <Navigate to={accueilPour(user.role)} replace />;

  const slide = SLIDES[i];

  return (
    <div
      style={{
        position: "relative", minHeight: "100dvh", width: "100%",
        background: "#5A1500", display: "flex", justifyContent: "center",
      }}
    >
      {/* Décor de fond (clippé indépendamment du contenu) */}
      <div aria-hidden style={{ position: "absolute", inset: 0, overflow: "hidden" }}>
        {SLIDES.map((s, idx) => (
          <div
            key={idx}
            style={{
              position: "absolute", inset: 0, backgroundImage: `url(${s.img})`,
              backgroundSize: "cover", backgroundPosition: "center",
              opacity: idx === i ? 1 : 0,
              transform: idx === i ? "scale(1.05)" : "scale(1)",
              transitionProperty: "opacity, transform", transitionDuration: "1s, 6s",
              transitionTimingFunction: "ease",
            }}
          />
        ))}
        <div
          style={{
            position: "absolute", inset: 0,
            background:
              "radial-gradient(120% 75% at 50% 0%, rgba(252,78,0,0.34) 0%, transparent 55%)," +
              "linear-gradient(180deg, rgba(50,12,0,0.42) 0%, rgba(80,18,0,0.58) 42%, rgba(70,16,0,0.97) 100%)",
          }}
        />
      </div>

      {/* En-tête marque — épinglé au coin */}
      <div
        style={{
          position: "absolute", zIndex: 2, display: "flex", alignItems: "center", gap: 12,
          top: "max(22px, env(safe-area-inset-top))", left: 24,
        }}
      >
        <div
          style={{
            width: 46, height: 46, background: "#FC4E00", borderRadius: 14, display: "flex",
            alignItems: "center", justifyContent: "center", boxShadow: "0 10px 30px rgba(252,78,0,0.55)",
          }}
        >
          <svg width="26" height="26" fill="none" viewBox="0 0 42 42">
            <path d="M14 28V16l14-4v12" stroke="white" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round" />
            <circle cx="11" cy="28" r="3" fill="white" />
            <circle cx="25" cy="24" r="3" fill="white" />
            <rect x="26" y="18" width="10" height="8" rx="2" stroke="white" strokeWidth="2.2" fill="none" />
          </svg>
        </div>
        <div>
          <div style={{ fontFamily: "Poppins, sans-serif", fontSize: 20, fontWeight: 800, color: "white", letterSpacing: -0.3, lineHeight: 1, textShadow: "0 2px 12px rgba(0,0,0,0.4)" }}>
            BookSpace
          </div>
          <div style={{ fontSize: 11, color: "rgba(255,255,255,0.7)", letterSpacing: 0.5, marginTop: 3 }}>Ouagadougou</div>
        </div>
      </div>

      {/* Contenu */}
      <div
        style={{
          position: "relative", zIndex: 2, width: "100%", maxWidth: 460, minWidth: 0, display: "flex", flexDirection: "column",
          justifyContent: "flex-end",
          padding: "0 26px calc(30px + env(safe-area-inset-bottom))",
        }}
      >
        {/* Accroche */}
        <div style={{ display: "flex", flexDirection: "column", paddingBottom: 26 }}>
          <div style={{ display: "flex", gap: 7, marginBottom: 22 }}>
            {SLIDES.map((_, idx) => (
              <button
                key={idx}
                aria-label={`Vue ${idx + 1}`}
                onClick={() => setI(idx)}
                style={{
                  height: 5, borderRadius: 3, border: "none", cursor: "pointer", padding: 0,
                  width: idx === i ? 26 : 8,
                  background: idx === i ? "#FC4E00" : "rgba(255,255,255,0.35)",
                  transition: "width 0.4s ease, background 0.4s ease",
                }}
              />
            ))}
          </div>

          <div key={i} className="animate-fadeinup">
            <h1
              style={{
                fontFamily: "Poppins, sans-serif", fontSize: "clamp(23px, 6vw, 32px)", fontWeight: 800,
                color: "white", lineHeight: 1.2, margin: 0, letterSpacing: -0.5, textWrap: "balance",
                textShadow: "0 2px 20px rgba(0,0,0,0.4)",
              }}
            >
              {slide.titre}
            </h1>
            <p style={{ fontSize: "clamp(13.5px, 3.7vw, 15px)", color: "rgba(255,255,255,0.82)", lineHeight: 1.55, margin: "12px 0 0", overflowWrap: "break-word" }}>
              {slide.sous}
            </p>
          </div>
        </div>

        {/* Actions */}
        <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
          <button
            onClick={() => navigate("/connexion")}
            style={{
              width: "100%", padding: "16px", background: "#FC4E00", color: "white", border: "none",
              borderRadius: 16, fontFamily: "inherit", fontSize: 15.5, fontWeight: 700, cursor: "pointer",
              letterSpacing: 0.2, boxShadow: "0 14px 34px rgba(252,78,0,0.45)",
            }}
          >
            Se connecter
          </button>
          <button
            onClick={() => navigate("/inscription")}
            style={{
              width: "100%", padding: "16px", background: "rgba(255,255,255,0.12)", color: "white",
              border: "1.5px solid rgba(255,255,255,0.5)", borderRadius: 16, fontFamily: "inherit",
              fontSize: 15.5, fontWeight: 700, cursor: "pointer", backdropFilter: "blur(6px)",
              WebkitBackdropFilter: "blur(6px)",
            }}
          >
            Créer un compte
          </button>
          <Link
            to="/inscription"
            style={{ textAlign: "center", fontSize: 13, color: "rgba(255,255,255,0.7)", textDecoration: "none", marginTop: 4 }}
          >
            Vous gérez un espace ? <span style={{ color: "#F3BB00", fontWeight: 600 }}>Publiez-le</span>
          </Link>
        </div>
      </div>
    </div>
  );
}
