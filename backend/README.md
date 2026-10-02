# BookSpace — API backend

API REST de la plateforme de réservation d'espaces (salles de répétition &
terrains de football), conforme au TDR de septembre 2026.

- **Stack** : Laravel 12 · PHP 8.2 · MySQL/MariaDB · Sanctum (auth par jeton)
- **Base de données** : `bookspace` (voir `.env`)

## Démarrage

```bash
cd backend
composer install
cp .env.example .env        # puis renseigner DB_* et APP_KEY
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve            # http://127.0.0.1:8000
```

### Comptes de démonstration (mot de passe : `password`)

| Rôle    | Téléphone       |
|---------|-----------------|
| Admin   | `+22600000000`  |
| Client  | `+22670000001`  |
| Gérant  | `+22670000010` … `+22670000013` |

## Modèle de données (TDR §7)

`users` (Client/Gérant/Admin) · `gerants` · `espaces` (salle_repetition |
terrain_foot, héritage single-table) · `espace_photos` · `espace_disponibilites`
(horaires hebdo) · `espace_blocages` · `reservations` · `paiements` (Mobile
Money) · `evaluations` · `app_notifications` · `litiges` · `parametres`
(commission, acompte…) · `audit_events` (historisation append-only, TDR §10.2).

## Points d'API principaux

| Méthode | Route | Rôle |
|---------|-------|------|
| POST | `/api/auth/register` · `/api/auth/login` | public |
| GET  | `/api/espaces` · `/api/espaces/{id}` · `/api/espaces/{id}/creneaux?date=` | public |
| POST | `/api/reservations` | client |
| POST | `/api/reservations/{id}/paiement` | client |
| POST | `/api/reservations/{id}/annuler` · `/evaluation` | client |
| GET/POST | `/api/gerant/espaces` (+ `PUT`, `soumettre`, `horaires`, `blocages`) | gérant |
| GET | `/api/gerant/reservations` · `/api/gerant/statistiques` | gérant |
| GET | `/api/admin/tableau-de-bord` | admin |
| POST | `/api/admin/gerants/{id}/valider` · `/api/admin/espaces/{id}/valider` | admin |
| PUT | `/api/admin/parametres/{cle}` | admin |
| POST | `/api/paiements/callback` | webhook passerelle Mobile Money |

Toutes les routes protégées attendent `Authorization: Bearer <token>`.

## Services externes (drivers interchangeables — `config/bookspace.php`)

**Mobile Money** (`mobile_money.driver`)
- `fake` (défaut, dev) : l'acompte est validé immédiatement.
- `orange` / `moov` : `app/Services/MobileMoney/{Orange,Moov}MoneyPasserelle.php`
  — trame d'intégration API opérateur, à compléter avec les identifiants
  marchands (`ORANGE_MOMO_*`, `MOOV_MOMO_*`). Confirmation via webhook
  `/api/paiements/callback` + polling `/api/reservations/{id}/paiement/statut`.

**SMS** (`sms.driver`)
- `log` (défaut) : le message part dans `storage/logs/laravel.log`.
- `http` : `app/Services/Sms/HttpSms.php` — POST générique vers un agrégateur
  (`SMS_HTTP_URL`, `SMS_HTTP_TOKEN`).

**Photos** (`photos_disk`) : `public` en dev (`php artisan storage:link`),
`s3` en prod.

## Tâches planifiées

`php artisan reservations:cloturer` (toutes les 15 min via `routes/console.php`)
passe les réservations confirmées au statut `terminee` une fois le créneau
écoulé, ce qui débloque l'évaluation. En prod : `* * * * * php artisan schedule:run`.

## Tests

`php artisan test` — 19 tests (auth, recherche, parcours réservation/paiement,
anti-double-réservation, annulation, évaluation, clôture, validation
gérant/espace, rôles, commission, statistiques).

## Règles métier garanties

- **Aucune double réservation** : verrou transactionnel + revérification de
  disponibilité à la création (TDR §15).
- Acompte (30 %) et commission plateforme (10 %) figés au moment de la
  réservation, paramétrables par l'admin.
- Un espace créé par un gérant est `en_attente` jusqu'à validation admin.
- Chaque transition (création, paiement, confirmation, annulation, validation)
  est journalisée dans `audit_events`.
