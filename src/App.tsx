import { lazy, Suspense } from "react";
import { Navigate, Route, Routes } from "react-router-dom";
import { AuthProvider } from "./context/AuthContext";
import { RedirectIfAuth, RequireAuth } from "./components/Guards";
import { Spinner } from "./components/ui";

// Onboarding/Login/Signup restent en chargement direct (premier écran, léger).
// Le reste est chargé à la demande : un visiteur non connecté ne télécharge
// jamais Leaflet ni les pages gérant/admin, ce qui allège nettement le
// chargement initial (un seul gros bundle sinon, cf. TDR §9 "connexion
// internet limitée tolérée").
import Onboarding from "./pages/Onboarding";
import Login from "./pages/Login";
import Signup from "./pages/Signup";

const Home = lazy(() => import("./pages/Home"));
const EspaceDetail = lazy(() => import("./pages/EspaceDetail"));
const Booking = lazy(() => import("./pages/Booking"));
const Payment = lazy(() => import("./pages/Payment"));
const Confirmation = lazy(() => import("./pages/Confirmation"));
const MyBookings = lazy(() => import("./pages/MyBookings"));
const ReservationDetail = lazy(() => import("./pages/ReservationDetail"));
const Notifications = lazy(() => import("./pages/Notifications"));
const Profile = lazy(() => import("./pages/Profile"));
const GerantDashboard = lazy(() => import("./pages/gerant/Dashboard"));
const GerantEspaces = lazy(() => import("./pages/gerant/Espaces"));
const GerantEspaceGestion = lazy(() => import("./pages/gerant/EspaceGestion"));
const GerantReservations = lazy(() => import("./pages/gerant/Reservations"));
const GerantDemandes = lazy(() => import("./pages/gerant/Demandes"));
const AdminDashboard = lazy(() => import("./pages/admin/Dashboard"));
const AdminValidations = lazy(() => import("./pages/admin/Validations"));
const AdminLitiges = lazy(() => import("./pages/admin/Litiges"));
const AdminParametres = lazy(() => import("./pages/admin/Parametres"));

function ChargementPage() {
  return (
    <div style={{ minHeight: "100dvh", display: "flex", alignItems: "center", justifyContent: "center" }}>
      <Spinner />
    </div>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <Suspense fallback={<ChargementPage />}>
      <Routes>
        <Route path="/" element={<Onboarding />} />
        <Route path="/connexion" element={<RedirectIfAuth><Login /></RedirectIfAuth>} />
        <Route path="/inscription" element={<RedirectIfAuth><Signup /></RedirectIfAuth>} />

        {/* Espace client */}
        <Route path="/app" element={<RequireAuth role="client"><Home /></RequireAuth>} />
        <Route path="/espaces/:id" element={<RequireAuth><EspaceDetail /></RequireAuth>} />
        <Route path="/espaces/:id/reserver" element={<RequireAuth role="client"><Booking /></RequireAuth>} />
        <Route path="/reservations" element={<RequireAuth role="client"><MyBookings /></RequireAuth>} />
        <Route path="/reservations/:id" element={<RequireAuth role="client"><ReservationDetail /></RequireAuth>} />
        <Route path="/reservations/:id/paiement" element={<RequireAuth role="client"><Payment /></RequireAuth>} />
        <Route path="/reservations/:id/confirmation" element={<RequireAuth role="client"><Confirmation /></RequireAuth>} />

        {/* Espace gérant */}
        <Route path="/gerant" element={<RequireAuth role="gerant"><GerantDashboard /></RequireAuth>} />
        <Route path="/gerant/espaces" element={<RequireAuth role="gerant"><GerantEspaces /></RequireAuth>} />
        <Route path="/gerant/espaces/:id" element={<RequireAuth role="gerant"><GerantEspaceGestion /></RequireAuth>} />
        <Route path="/gerant/reservations" element={<RequireAuth role="gerant"><GerantReservations /></RequireAuth>} />
        <Route path="/gerant/demandes" element={<RequireAuth role="gerant"><GerantDemandes /></RequireAuth>} />

        {/* Console admin */}
        <Route path="/admin" element={<RequireAuth role="admin"><AdminDashboard /></RequireAuth>} />
        <Route path="/admin/validations" element={<RequireAuth role="admin"><AdminValidations /></RequireAuth>} />
        <Route path="/admin/litiges" element={<RequireAuth role="admin"><AdminLitiges /></RequireAuth>} />
        <Route path="/admin/parametres" element={<RequireAuth role="admin"><AdminParametres /></RequireAuth>} />

        {/* Commun (client + gérant) */}
        <Route path="/notifications" element={<RequireAuth><Notifications /></RequireAuth>} />
        <Route path="/profil" element={<RequireAuth><Profile /></RequireAuth>} />

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
      </Suspense>
    </AuthProvider>
  );
}
