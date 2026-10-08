# BookSpace

Plateforme mobile-first de réservation d'espaces (salles de répétition &
terrains de football) à Ouagadougou, conforme au TDR de septembre 2026
(`docs/TDR_Plateforme_Reservation_Espaces.pdf`).

- **Stack** : Laravel 12 · PHP 8.2 · Blade + Livewire 3 · Tailwind CSS v4 ·
  Leaflet · MySQL/MariaDB
- **Base de données** : `bookspace` (voir `.env`)
- **Rôles** : client, gérant (propriétaire d'espaces), administrateur

## Démarrage

```bash
composer install
npm install
cp .env.example .env        # puis renseigner DB_*
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link    # photos des espaces
npm run build               # CSS/JS -> public/build
php artisan serve --host=127.0.0.1 --port=8000
```

Ouvrir <http://127.0.0.1:8000>. Sous Windows/XAMPP : démarrer MySQL puis
lancer `start.bat`.

En développement, `npm run dev` (dans un second terminal) recharge les pages à
chaque modification de vue, de CSS ou de JS.

### Comptes de démonstration (mot de passe : `password`)

| Rôle    | Téléphone       | Espace            |
|---------|-----------------|-------------------|
| Admin   | `+22600000000`  | `/admin`          |
| Client  | `+22670000001`  | `/app`            |
| Gérant  | `+22670000010` … `+22670000013` | `/gerant` |

## Écrans

| Rôle | Pages |
|------|-------|
| Visiteur | `/` (accueil), `/connexion`, `/inscription` |
| Client | `/app` (recherche, « près de moi »), `/espaces/{id}` (fiche, carte, itinéraire), `/espaces/{id}/reserver`, `/reservations`, `/reservations/{id}` (annulation, demande de report, avis), `/reservations/{id}/paiement`, `/reservations/{id}/confirmation` |
| Gérant | `/gerant` (statistiques), `/gerant/espaces`, `/gerant/espaces/{id}` (photos, localisation, horaires, blocages), `/gerant/reservations` (calendrier, report, annulation, ajout manuel), `/gerant/demandes` |
| Admin | `/admin` (supervision), `/admin/validations` (gérants, espaces, suspension), `/admin/litiges`, `/admin/parametres` |
| Tous | `/notifications`, `/profil` |

Chaque écran est un composant Livewire (`app/Livewire/`, vues dans
`resources/views/livewire/`). L'authentification web est une session Laravel
classique, par numéro de téléphone.

## Modèle de données (TDR §7)

`users` (Client/Gérant/Admin) · `gerants` · `espaces` (salle_repetition |
terrain_foot, héritage single-table) · `espace_photos` · `espace_disponibilites`
(horaires hebdo) · `espace_blocages` · `reservations` · `paiements` (Mobile
Money) · `evaluations` · `demandes_modification` · `app_notifications` ·
`litiges` · `parametres` (commission, acompte…) · `audit_events`
(historisation append-only, TDR §10.2).

## API JSON

L'application web n'utilise pas l'API ; elle est conservée pour les **webhooks
de paiement** et un éventuel client mobile (auth par jeton Sanctum,
`Authorization: Bearer <token>`).

| Méthode | Route | Rôle |
|---------|-------|------|
| POST | `/api/paiements/callback` · `/api/paiements/callback/yenga` | webhook passerelle Mobile Money (signature HMAC) |
| POST | `/api/auth/register` · `/api/auth/login` | public |
| GET  | `/api/espaces` · `/api/espaces/{id}` · `/api/espaces/{id}/creneaux?date=` | public |
| POST | `/api/reservations` · `/api/reservations/{id}/paiement` | client |
| …    | `/api/gerant/*` · `/api/admin/*` | gérant · admin |

Liste complète : `php artisan route:list --path=api`.

## Services externes (drivers interchangeables — `config/bookspace.php`)

**Mobile Money** (`MOMO_DRIVER`)
- `fake` (défaut, dev) : l'acompte est validé immédiatement.
- toute autre valeur : passerelle réelle. Le paiement passe par **Yenga**
  (`YENGA_*`) : le client est redirigé vers la page de paiement hébergée, la
  confirmation arrive par le webhook `/api/paiements/callback/yenga`, et
  l'écran de paiement sonde le statut au retour du client.

**SMS** (`SMS_DRIVER`)
- `log` (défaut) : le message part dans `storage/logs/laravel.log`.
- `africastalking` (`AT_USERNAME`, `AT_API_KEY`) ou `http` (agrégateur
  générique, `SMS_HTTP_URL`, `SMS_HTTP_TOKEN`).

**Photos** (`PHOTOS_DISK`) : `public` en dev (`php artisan storage:link`),
`s3` en prod. Les photos sont converties en WebP à l'envoi.

**Cartes** : tuiles OpenStreetMap (Leaflet), géocodage Nominatim et
itinéraires OSRM (`NOMINATIM_URL`, `OSRM_URL`) — sans clé.

## Tâches planifiées

Définies dans `routes/console.php` ; en prod : `* * * * * php artisan schedule:run`.

- `reservations:expirer` (5 min) : annule les réservations dont l'acompte n'a
  pas été réglé à temps et libère le créneau.
- `reservations:cloturer` (15 min) : passe les réservations confirmées à
  `terminee` une fois le créneau écoulé, ce qui débloque l'évaluation.
- `reservations:rappeler` (5 min) : rappel SMS client + gérant avant le créneau.

## Tests

`php artisan test` — application web Livewire (`tests/Feature/Web/` :
authentification et rôles, parcours client, espace gérant, console admin) et
API/services (réservation, paiement et webhooks, anti-double-réservation,
demandes, expiration, rappels, photos, géolocalisation…).

Les tests tournent sur SQLite en mémoire avec les pilotes `fake` / `log` : ils
n'appellent jamais la passerelle de paiement ni l'envoi de SMS réels.

## Règles métier garanties

- **Aucune double réservation** : verrou transactionnel + revérification de
  disponibilité à la création (TDR §15).
- Acompte (30 %) et commission plateforme (10 %) figés au moment de la
  réservation, paramétrables par l'admin.
- Un espace créé par un gérant est `en_attente` jusqu'à validation admin.
- Une réservation confirmée ne s'annule ou ne se reporte côté client que sur
  demande validée par le gérant.
- Chaque transition (création, paiement, confirmation, annulation, validation)
  est journalisée dans `audit_events`.
