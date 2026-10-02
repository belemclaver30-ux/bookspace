# BookSpace

Plateforme mobile-first de réservation d'espaces (studios de répétition, terrains de football) à Ouagadougou. Cadrée par `src/imports/TDR_Plateforme_Reservation_Espaces__1_.pdf`. Trois rôles : client, gérant (propriétaire d'espaces), admin.

Ce dépôt contenait à l'origine une maquette générée par Figma Make (un unique `App.tsx` de démo). Elle a été remplacée le 2026-09-09 par la vraie application (frontend React connecté à une API Laravel réelle). L'outillage Vite/Figma Make d'origine (dev server, plugins) est resté, mais **le code applicatif ci-dessous est le seul à suivre**.

## Deux serveurs à faire tourner

Le dépôt contient deux projets distincts :

- **Frontend** (racine du dépôt) — React + Vite, servi sur `$PORT` (défaut 8443)
- **`backend/`** — API Laravel 12 + MySQL, servie séparément (`php artisan serve`, port 8000)

Sur cette machine (Windows + XAMPP), voir `start.bat` ou :
```
# MySQL (XAMPP) doit tourner sur le port 3306, base "bookspace"
cd backend && php artisan serve --host=127.0.0.1 --port=8000
npm run dev            # PORT=8443 par défaut, cf. .env : VITE_API_URL=http://127.0.0.1:8000/api
```
Utiliser `127.0.0.1`, pas `localhost` (`php artisan serve` n'écoute qu'en IPv4).

## Structure frontend (`src/`)

- `main.tsx` — entrypoint, monte `App.tsx` dans un `BrowserRouter`
- `App.tsx` — toutes les routes (`react-router-dom` v7), protégées par rôle via `components/Guards.tsx` (`RequireAuth`/`RedirectIfAuth`)
- `context/AuthContext.tsx` — session utilisateur + token Sanctum (localStorage)
- `lib/api.ts` — client fetch (+ `apiUpload` pour multipart), écoute les 401 (`UNAUTHORIZED_EVENT`)
- `lib/useApi.ts`, `lib/routes.ts` (routage par rôle via `accueilPour()`), `lib/types.ts`, `lib/format.ts`, `lib/geo.ts`
- `components/` — `DashboardShell.tsx` (sidebar/tiroir, base des layouts), `AppLayout`/`GerantLayout`/`AdminLayout`, `SpaceCard`, `charts.tsx` (SVG maison : `AreaTrend`, `Donut`, `BarsH`, `Jauge`), `MapView`/`Itineraire`/`LocalisationEditor` (Leaflet), `PhotosEditor`, `CalendrierMois`, `DemandeClient`, `ReservationGerantCard`, `NotificationBell`
- `pages/` — parcours client (Onboarding, Login, Signup, Home, EspaceDetail, Booking, Payment, Confirmation, MyBookings, ReservationDetail, Notifications, Profile) ; `pages/gerant/` (Dashboard, Espaces, EspaceGestion, Reservations, Demandes) ; `pages/admin/` (Dashboard, Validations, Litiges, Parametres)

Palette de marque dans `index.css :root` : orange `#FC4E00` (actions), `#BD3100` (surfaces sombres), `#F3BB00`/`#EB9F04` (accents/attente).

## Structure backend (`backend/`)

Laravel 12, auth Sanctum par numéro de téléphone. Routes publiques : `POST /api/auth/{register,login}`, `GET /api/parametres-publics`, `GET /api/espaces[/{id}][/creneaux]`, `POST /api/paiements/callback` (webhook, signé HMAC-SHA256 si `MOMO_WEBHOOK_SECRET` défini). Routes protégées (`auth:sanctum`) + groupes par rôle `role:gerant` (préfixe `gerant/`) et `role:admin` (préfixe `admin/`).

Logique métier notable : verrou transactionnel anti-double-réservation, expiration auto des réservations impayées (`reservations:expirer`, planifiée), clôture auto (`reservations:cloturer`), drivers interchangeables Mobile Money (`app/Services/MobileMoney/`, `fake` en dev) et SMS (`app/Services/Sms/`), géolocalisation via Nominatim/OSRM (`app/Services/GeoService.php`), historisation `audit_events`.

Tests : `php artisan test` (PHPUnit, base SQLite en test). Dev local : base MySQL `bookspace` (XAMPP/MariaDB, `root` sans mot de passe).

## Dépendances frontend

- Runtime : React 19, React Router 7, Leaflet (cartes)
- Styling : Tailwind CSS v4 (`@tailwindcss/vite`), `index.css` = `@import 'tailwindcss';` + tokens de marque
- Build : Vite 8, TypeScript 5.7, `@vitejs/plugin-react`
- Formatting : oxfmt

## Code quality

- Guillemets doubles pour les chaînes contenant une apostrophe (`"C'est prêt"`), ou échapper dans une chaîne à guillemets simples — une apostrophe non échappée casse le build.
- Vérifier que les balises JSX sont fermées et les accolades équilibrées.
- Export des composants en `export default`.
