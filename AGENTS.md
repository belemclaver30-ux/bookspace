# BookSpace

Plateforme mobile-first de réservation d'espaces (studios de répétition, terrains de football) à Ouagadougou. Cadrée par `docs/TDR_Plateforme_Reservation_Espaces.pdf`. Trois rôles : client, gérant (propriétaire d'espaces), admin.

**Application 100 % Laravel** : Laravel 12 + Blade + Livewire 3 (Alpine.js fourni par Livewire), un seul projet à la racine du dépôt, un seul serveur. L'ancien frontend React/Vite (Figma Make) et le dossier `backend/` ont été supprimés le 2026-10-02 — ne pas les recréer.

## Lancer l'application

Sur cette machine (Windows + XAMPP), voir `start.bat` ou :
```
# MySQL (XAMPP) doit tourner sur le port 3306, base "bookspace"
npm run build          # compile CSS/JS dans public/build (ou `npm run dev` pour le rechargement à chaud)
php artisan serve --host=127.0.0.1 --port=8000
```
Ouvrir `http://127.0.0.1:8000` (pas `localhost` : `php artisan serve` n'écoute qu'en IPv4). Comptes de démo dans le `README.md`.

## Structure

- `routes/web.php` — toutes les pages (composants Livewire pleine page), groupées par rôle : `guest`, `auth`, `role:client`, `role:gerant` (préfixe `gerant/`), `role:admin` (préfixe `admin/`). Auth par **session** (connexion par numéro de téléphone).
- `app/Livewire/` — un composant par écran :
  - `Auth/` (Connexion, Inscription)
  - `Client/` (Accueil, EspaceFiche, Reserver, Paiement, Confirmation, MesReservations, ReservationDetail)
  - `Gerant/` (TableauDeBord, Espaces, EspaceGestion, Reservations, Demandes)
  - `Admin/` (TableauDeBord, Validations, Gerants = coordonnées de versement et reversements, Litiges, Parametres)
  - `Notifications`, `Profil` (communs) ; `Profil/` (Compte = photo, infos, changement de numéro vérifié par SMS, compte de versement Mobile Money du gérant ; Securite = mot de passe, déconnexion des autres appareils, suppression du compte ; Preferences = SMS/rappels ; Aide = FAQ par rôle + contact + signalement → `Litige` ; Favoris (client) ; Revenus (gérant, 12 mois))
  - `Auth/MotDePasseOublie` — réinitialisation par code SMS
  - `Messages/` (Boite = boîte de réception, Fil = discussion, rafraîchie toutes les 10 s) — messagerie client ↔ gérant, ouverte depuis une réservation (`/messages/reservation/{id}`) ; rôles client et gérant uniquement
  - `Notifications` — notifications dépliables au clic (texte complet + lien vers la page concernée), marquées lues à l'ouverture
- `resources/views/livewire/` — vues des composants ; `resources/views/onboarding.blade.php` — accueil public.
- `resources/views/legal/` — conditions d'utilisation et politique de confidentialité (pages publiques `/conditions`, `/confidentialite`). **Projets de texte à faire valider par un juriste.**
- `resources/views/components/` — composants Blade : `layouts/` (`base`, `ecran` = colonne mobile sans navigation, `client` = barre basse/latérale, `dashboard` = back-office gérant/admin), `graph/` (SVG maison rendus côté serveur : `aire`, `anneau`, `barres`, `jauge`), `icone`, `erreur`, `badge-statut`, `carte-espace`, `cloche`, `retour`, `panneau`, `ligne`, `vide`.
- `resources/js/` — `app.js` (démarre Livewire/Alpine), `cartes.js` (cartes Leaflet chargées à la demande : `carteItineraire`, `carteLocalisation`).
- `resources/css/app.css` — Tailwind v4 + tokens de marque (`@theme`) + classes utilitaires maison (`btn-primaire`, `champ`, `carte`, `puce`…).
- `app/Support/helpers.php` — aides de vue : `fcfa()`, `date_longue()`, `plage_horaire()`, `libelle_type()`…
- `routes/api.php` + `app/Http/Controllers/Api/` — API JSON (Sanctum) conservée pour les **webhooks de paiement** (`POST /api/paiements/callback[/yenga]`, signés HMAC) et un éventuel client mobile. L'application web ne l'appelle pas.

Palette de marque (`resources/css/app.css`) : orange `#FC4E00` (`brand`, actions), `#BD3100` (`brand-dark`, surfaces sombres), `#F3BB00` (`gold`) / `#EB9F04` (`attente`).

## Logique métier (`app/Services/`)

Les composants Livewire et les contrôleurs d'API délèguent aux mêmes services — **ne pas dupliquer de règle métier dans un composant** :

- `ReservationService` — création (verrou transactionnel anti-double-réservation), réservation manuelle du gérant, report, annulation.
- `DisponibiliteService` — horaires, blocages, créneaux du jour.
- `PaiementService` + `MobileMoney/` — drivers interchangeables (`fake` en dev, `yenga`…).
- `DemandeService` — demandes client d'annulation/report validées par le gérant.
- `NotificationService` + `Sms/` — fil in-app + SMS (`log` en dev). Respecte les préférences de l'utilisateur (`users.notif_sms`, `users.notif_rappels`) : passer par `notifier()`, ne jamais appeler la passerelle SMS directement pour un utilisateur ayant un compte.
- `StatistiquesService` — tableaux de bord gérant et admin.
- `ReversementService` — part des acomptes due aux gérants (réservations en ligne **terminées**, hors réservations manuelles) ; l'admin enregistre le virement Mobile Money fait hors application (table `reversements`, `reservations.reversement_id`).
- `PhotoEspaceService` / `ImageConversionService` — photos converties en WebP.
- `InscriptionService`, `GeoService` (Nominatim/OSRM).
- `CodeVerificationService` — codes SMS à 6 chiffres (hachés, 10 min, 5 essais, 3 envois/h par numéro) ; partent même si l'utilisateur a coupé les SMS.
- `MessagerieService` — une conversation par couple (client, gérant), possible seulement s'ils ont une réservation en commun ; compteur `nonLus()` des pastilles de navigation.
- `CompteService` — « supprimer mon compte » = **anonymisation** (pas de `delete()` sur `users` : les clés étrangères effaceraient en cascade réservations, paiements et avis).

Tâches planifiées (`routes/console.php`) : `reservations:expirer`, `reservations:cloturer`, `reservations:rappeler`. Historisation dans `audit_events`.

## Tests

`php artisan test` (PHPUnit, SQLite en mémoire). `tests/Feature/Web/` couvre l'application Livewire, `tests/Feature/*.php` l'API et les services. `phpunit.xml` force `MOMO_DRIVER=fake` et `SMS_DRIVER=log` : les tests n'appellent jamais Yenga ni la passerelle SMS réelle, même si `.env` les active.

## Conventions

- Code et interface en français (noms de classes, méthodes, vues).
- Interactivité : Livewire d'abord ; Alpine uniquement pour l'état purement visuel (onglets, tiroir, galerie) et les API navigateur (géolocalisation, Leaflet).
- Middleware `AuthenticateSession` actif : changer le mot de passe d'un utilisateur ferme ses autres sessions (reconnecter la session courante avec `Auth::login()`).
- Toute action Livewire revérifie l'appartenance de la ressource (client propriétaire de la réservation, gérant propriétaire de l'espace). Le rôle est contrôlé par le middleware `role:` (rejoué sur les requêtes Livewire).
- Pastilles « non lus » : `<x-pastille :nombre="…" compteur="notifications|messages">` ; un composant qui change ce nombre émet `$this->dispatch('compteur', type: …, nombre: …)` pour les mettre à jour sans recharger.
- Bouton retour : toujours `<x-retour href="…">` — il revient à la page précédente de l'application (`resources/js/retour.js`) ; `href` n'est que la destination de secours (lien ouvert directement). Ne pas ouvrir de page interne dans un nouvel onglet (`target="_blank"`) : le retour n'y aurait pas d'historique. Un formulaire qu'on peut quitter pour lire une autre page garde sa saisie (`#[Session]`, ou `#[Url]` pour filtres et pagination).
- Champs de formulaire : police ≥ 16 px (évite le zoom iOS), un `<label>` par champ.
- Après modification de CSS/JS : `npm run build` (`public/build` n'est pas versionné).
