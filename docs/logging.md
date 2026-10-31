# Logging et alertes

Conventions de journalisation de Koinonia. Tout reste **local** : fichiers dans
`storage/logs/` et e-mails d'alerte envoyés par le SMTP de l'application. Aucun
service externe (Sentry, Slack, Papertrail…) ni SDK tiers.

## 1. Canaux

Configurés dans `config/logging.php` : fichiers quotidiens, rétention `LOG_DAILY_DAYS`
(30 jours), format JSON en production via `LOG_FORMATTER=Monolog\Formatter\JsonFormatter`.

| Canal | Fichier | Contenu |
|-------|---------|---------|
| `app` | `app-AAAA-MM-JJ.log` | Événements et erreurs métier (réservations, repas, menus, bulletins…), alertes `alert.*`, échecs de broadcast. |
| `security` | `security-*.log` | Connexions, déconnexions, refus d'accès (403), sessions expirées (419), throttling (429), demandes de compte. |
| `mail` | `mail-*.log` | Envois et échecs d'e-mails (SafeMailSender, alertes non envoyées). |
| `queue` | `queue-*.log` | Jobs, échecs de jobs, commandes planifiées. |
| `audit` | `audit-*.log` | Actions d'administration à tracer (niveau `info` minimum). |
| `laravel` (stack par défaut) | `laravel.log` | Exceptions non gérées reportées par `App\Exceptions\Handler`, logs sans canal explicite. |

Choisir le canal explicitement : `Log::channel('app')->warning(...)` ou via le trait
`App\Traits\LogsErrors`.

## 2. Niveaux

| Niveau | Quand |
|--------|-------|
| `debug` | Diagnostic temporaire, jamais en production de manière durable. |
| `info` | Événement métier normal et notable (réservation créée, menu publié, connexion réussie). |
| `notice` | Événement normal mais à surveiller en volume (419, 429). |
| `warning` | Anomalie gérée : conflit, donnée incohérente, service externe dégradé (broadcast, mail en échec avec repli). |
| `error` | Échec d'une opération attendue par l'utilisateur, avec l'exception (`logError`). |
| `critical` | Réservé aux alertes (`alert.<règle>`) : écrit par `AlertService`. |

## 3. Contexte partagé

Le middleware `App\Http\Middleware\AddRequestContext` ajoute automatiquement à **tous**
les logs d'une requête : `request_id`, `user_id`, `roles`, `route`, `method`, `url`, `ip`.
Le même contexte est ajouté aux exceptions reportées par le handler (plus `exception_class`).

- Ne pas répéter ces champs dans les appels : ne passer que le contexte métier
  (`reservation_id`, `vehicle_id`, `date`…).
- L'en-tête `X-Request-Id` est renvoyé sur chaque réponse, et le request_id est affiché
  sur les pages d'erreur et dans les réponses JSON d'erreur (`request_id`). Un utilisateur
  peut donc le communiquer au support ; on le retrouve avec
  `grep <request_id> storage/logs/*.log`.

## 4. Nommage des événements

Le message d'un log est une **clé d'événement stable**, en anglais, en minuscules et
séparée par des points : `<domaine>.<objet>.<action|état>`.

Exemples : `reservation.conflict`, `reservation.store.failed`, `auth.login.failed`,
`meal.inscription.created`, `broadcast.failed`, `alert.error_spike`.

Les détails variables vont dans le tableau de contexte, jamais dans la clé.

```php
use App\Traits\LogsErrors;

class VehicleReservationController extends Controller
{
    use LogsErrors;

    // ...
    try {
        // ...
    } catch (\Throwable $e) {
        $this->logError('reservation.store.failed', $e, ['vehicle_id' => $vehicle->id]);
        // ...
    }

    $this->logWarning('reservation.conflict', ['vehicle_id' => $vehicle->id, 'date' => $date]);
    $this->logInfo('auth.login.succeeded', [], 'security');
}
```

`logError()` ajoute `exception_class`, `error`, `location` (fichier:ligne) et
`exception` (la stack trace est conservée par Monolog).

## 5. Ce qu'il ne faut jamais journaliser

- Mots de passe (même invalides), mots de passe temporaires, tokens (CSRF, reset,
  API, `remember_token`), identifiants de session, clés Pusher/SMTP.
- Contenu des QR codes et liens signés.
- Corps complets d'e-mails ou de messages.
- Données personnelles inutiles : téléphone, date de naissance, adresse. Préférer
  l'identifiant (`user_id`) à l'e-mail.
- Une adresse e-mail, quand elle est nécessaire (ex. échec de connexion d'un compte
  inconnu, échec d'envoi), est **masquée** :

```php
use App\Support\LogMask;

Log::channel('security')->warning('auth.login.failed', ['email' => LogMask::email($email)]);
// "jean.dupont@example.com" => "j***@example.com"
```

## 6. Erreurs HTTP et pages d'erreur

`App\Exceptions\Handler` :

- ne reporte pas le bruit attendu : validation, 404, authentification/autorisation,
  CSRF (419), throttling (429). Les 419 et 429 sont tracés au niveau `notice` dans
  `security` (`security.csrf.mismatch`, `security.throttled`) ;
- affiche les vues `resources/views/errors/*.blade.php` (gabarit commun `layout.blade.php`)
  pour 401, 403, 404, 419, 429, 500, 503, et `4xx`/`5xx` pour les autres codes ;
- répond aux requêtes JSON (`expectsJson()`) par
  `{"message": "<message français>", "status": <code>, "request_id": "<id>"}`.
  Les erreurs de validation gardent le format Laravel standard (422 + `errors`) ;
- n'expose jamais de stack trace ni de message interne hors `APP_DEBUG=true`.

Un `abort(403, 'Message')` explicite garde son message ; pour 404, 419, 429, 5xx, le
message français générique (`Handler::MESSAGES`) est toujours utilisé.

## 7. Alertes

Une alerte = une ligne `critical` `alert.<règle>` dans `app` **et** un e-mail immédiat
(jamais mis en file : la queue peut être en panne) aux destinataires :
`ALERT_MAIL_TO` (liste séparée par des virgules), sinon les administrateurs actifs.

Le service `App\Services\AlertService` :

```php
use App\Services\AlertService;

// Alerte directe (ex. job en échec, scheduler inactif)
app(AlertService::class)->send('queue_failure', 'Un job a échoué.', ['job' => $name]);

// Alerte sur seuil : envoyée quand N occurrences arrivent dans la fenêtre
app(AlertService::class)->hit(
    'mail_failure',
    'Échecs répétés d\'envoi d\'e-mails.',
    ['mailable' => $class],
    config('monitoring.mail_failure.threshold'),
    config('monitoring.mail_failure.window_minutes'),
);
```

- **Throttle** : un seul e-mail par règle toutes les `ALERT_THROTTLE_MINUTES` (30 min) ;
  chaque occurrence reste visible dans le log.
- **Désactivation** : `ALERT_ENABLED=false` coupe les e-mails, pas la ligne de log.
- **Échec SMTP** : tracé en `error` dans `mail` (`alert.mail.failed`), sans créer de
  `FailedEmail` ni déclencher d'autre alerte (pas de boucle).

Règles et réglages (`config/monitoring.php`, variables dans `.env.example`) :

| Règle | Déclencheur | Réglage |
|-------|-------------|---------|
| `error_spike` | Exceptions reportées (5xx), branché dans `Handler::register()` | `ALERT_ERROR_SPIKE_THRESHOLD` / `_WINDOW` (10 en 5 min) |
| `mail_failure` | Échecs d'envoi d'e-mails | `ALERT_MAIL_FAILURE_THRESHOLD` / `_WINDOW` (3 en 15 min) |
| `broadcast_failure` | Échecs de `broadcastSafely()` | `ALERT_BROADCAST_FAILURE_THRESHOLD` / `_WINDOW` (5 en 10 min) |
| `queue_failure` | Job en échec | `ALERT_QUEUE_FAILURE` |
| `scheduler_stale` | `SchedulerHeartbeat::isStale()` | `MONITORING_HEARTBEAT_MAX_AGE` (5 min) |

Seuils de lenteur : `MONITORING_SLOW_QUERY_MS` (500 ms), `MONITORING_SLOW_REQUEST_MS` (2000 ms).

## 8. Helpers disponibles

| Helper | Rôle |
|--------|------|
| `App\Traits\LogsErrors` | `logError($event, $e, $context, $channel)`, `logWarning(...)`, `logInfo(...)`. |
| `App\Traits\BroadcastsSafely` | `broadcastSafely($event, $context, $toOthers)` : diffuse sans jamais lever d'exception, log `broadcast.failed` + compteur `broadcast_failure`. Retourne `true` si diffusé. |
| `App\Services\AlertService` | `send()` / `hit()`, voir ci-dessus. |
| `App\Support\SchedulerHeartbeat` | `beat()` (à appeler par le scheduler), `lastBeat()`, `isStale()`. Stocké dans le cache par défaut : le cron et le web doivent partager le même store (`CACHE_DRIVER=file` sur OVH). |
| `App\Support\LogMask` | `email()` : masque une adresse avant de la journaliser. |

## 9. Utilisateurs, communautés et demandes (issue #24)

Contrôleurs : `UserController`, `CommunityController`, `CommunityRequestController`,
`CommunityRequestFormController`, `UserProfileVehicleTypeController`,
`Admin\VehicleSettingController`.

- **Échec d'écriture** (exception SQL…) : `error` dans `app` (`<domaine>.<action>.failed`,
  ex. `user.store.failed`, `community_request.store.failed`), puis retour au formulaire
  avec un message flash `error` en français (saisie conservée, sauf mots de passe).
  Une suppression de membre en échec annule la transaction (`rolled_back: true`).
- **Refus métier** : `warning` dans `app` avec un champ `reason`
  (`user.destroy.refused` : `self` / `last_admin`, `community.destroy.refused` :
  `has_users` / `has_requests`, `profile_vehicle_type.*.refused`,
  `community_request.form.session_expired`, `community_request.form.location_missing`).
- **Accès refusé** à une demande d'un autre membre ou à une action admin :
  `community_request.access.denied` (`warning`, canal `security`).
- **Audit** (`audit`, `info`) : `user.created`, `user.updated` (noms des champs modifiés,
  anciens/nouveaux rôles), `user.active_toggled`, `community.created|updated|deleted`,
  `community_request.updated|deleted|exported`, `profile_vehicle_type.attached|updated|detached`,
  `vehicle_settings.updated` (anciennes et nouvelles valeurs).
- **Pas de doublon avec `ActivityLog`** : la suppression d'un membre est déjà tracée
  par `ActivityLog::log('user.deleted')` ; aucune ligne `audit` n'est ajoutée pour elle.
- Jamais journalisés : e-mails des demandeurs et membres (seulement masqués en cas
  d'échec de création), descriptions des demandes, notes admin, URL/contenu du QR code.

## 9. Véhicules, réservations et lieux de réparation

`VehicleReservationController`, `VehicleController`, `RepairLocationController` (issue #26).
Contexte d'une réservation : `reservation_id`, `vehicle_id`, `owner_id` (titulaire ; l'acteur
est le `user_id` partagé), `date`, `start_time`, `end_time`, `status`. Jamais `destination`
ni `notes` (texte libre) : seuls les **noms** des champs modifiés sont journalisés (`fields`).

| Événement | Canal | Niveau | Quand |
|-----------|-------|--------|-------|
| `reservation.created` / `.updated` / `.cancelled` | `app` | info | Création, modification, annulation (`by_admin`). |
| `reservation.conflict` | `app` | warning | Créneau indisponible. `action` (store/update), `reason` : `overlap`, `vehicle_unavailable`, `concurrent_insert` (409, index unique). |
| `reservation.cancel.refused` | `app` | warning | Réservation terminée ou déjà rejetée/annulée. |
| `reservation.access.denied` | `security` | warning | Ni titulaire ni admin (`action` : show, edit, update, cancel, approve, reject, store). |
| `reservation.approved` / `.rejected` | `audit` | info | Décision d'un admin (`previous_status`). |
| `reservation.store.failed` / `.update.failed` | `app` | error | Erreur SQL inattendue, puis l'exception est relancée (page 500 + `error_spike`). |
| `broadcast.failed` | `app` | warning | Diffusion `ReservationCreated/Updated/Cancelled` en échec (`event`) ; l'action aboutit quand même, l'alerte `broadcast_failure` surveille la fréquence. |
| `vehicle.created` / `.updated` / `.deleted` / `.availability.toggled` | `audit` | info | Gestion du parc par un admin. |
| `vehicle.delete.refused` | `app` | warning | Réservations à venir (`future_reservations`). |
| `vehicle.*.failed` | `app` | error | Échec d'écriture ; l'utilisateur est renvoyé au formulaire avec un message. |
| `repair_location.created` / `.updated` / `.deleted` | `audit` | info | Gestion des lieux de réparation. |
| `repair_location.delete.refused` | `app` | warning | Demandes liées (`linked_requests`). |
| `repair_location.*.failed` | `app` | error | Échec d'écriture, retour au formulaire avec un message. |

Diagnostic : `grep '"reservation.conflict"' storage/logs/app-*.log` pour les créneaux
disputés, `grep broadcast.failed` si le planning ne se met plus à jour en temps réel
(le polling `/api/v1` prend le relais).

## 9. Emails (issue #28)

Tout envoi passe par `App\Traits\SafeMailSender` (`sendMailSafely`, `sendMailAfterResponse`,
`sendMailSafelyToMany`). Un échec ne casse jamais la requête et laisse **toujours** :

1. une entrée `failed_emails`, visible dans *Administration > Emails échoués*
   (`admin.failed-emails.*`) avec son numéro `#id`, le nombre de tentatives et le request_id ;
2. une ligne `error` `mail.send.failed` dans `mail-*.log` avec `failed_email_id` (le même
   `#id`), `mailable`, `recipient` (masqué), `attempt`, `source`, `related_model`,
   `exception_class`, `error` ;
3. une occurrence du compteur d'alerte `mail_failure` (section 7).

| `source` | Cas |
|----------|-----|
| `sync` | Envoi direct (`$immediate = true`, `sendMailAfterResponse`) ou échec de mise en file. |
| `queue` | Mailable `ShouldQueue` en échec définitif dans le worker (après `--tries=3`) : `failed()` du trait `App\Mail\Concerns\RecordsDeliveryFailure`. Le log porte `origin_request_id`, la requête qui a demandé l'envoi. |

Corréler : `grep '"failed_email_id":42' storage/logs/mail-*.log`, ou rechercher le
`request_id` de l'entrée dans tous les logs.

Autres événements du canal `mail` : `mail.sent` / `mail.queued` (`info`),
`mail.batch.partial_failure` (`warning`), `mail.retry.succeeded` (`info`),
`mail.retry.failed` (`error`, compté dans `mail_failure`), `mail.retry.unavailable`
(`warning`, entrée sans contenu conservé), `mail.failed_email.store_failed` (la base
est indisponible : le log reste la seule trace).

Le bouton **Renvoyer** rejoue le mailable conservé (colonne `payload`, sérialisée et
**chiffrée** avec `APP_KEY` car elle peut contenir un mot de passe temporaire) en envoi
immédiat ; succès ou échec incrémentent `attempts`. Les actions de l'administrateur sont
tracées dans `audit` (`admin.failed_email.retried|resolved|deleted`). Un nouveau mailable
doit utiliser le trait `RecordsDeliveryFailure` pour que ses échecs en file soient tracés.

<!-- Runbook d'erreurs (diagnostic par symptôme) : à compléter (issue #32). -->

## 9. Sécurité et authentification (canal `security`)

Événements tracés (issue #23). Utilisateur, rôles, route et IP viennent du contexte
partagé ; les e-mails sont toujours masqués, jamais de mot de passe ni de token.

| Événement | Niveau | Source | Contexte métier |
|-----------|--------|--------|-----------------|
| `auth.login.succeeded` | info | `LogAuthenticationEvents` (Login) | `user_id`, `remember` |
| `auth.login.failed` | warning | Failed / `AuthController` | `reason` (`invalid_credentials`, `unknown_account`, `inactive_account`), `email` masqué |
| `auth.lockout` | warning | Lockout (5 échecs e-mail+IP en 60 s) | `email` masqué ; alerte `auth_lockout` si répété |
| `auth.logout` | info | Logout | `user_id` |
| `auth.password.reset_link.sent` / `.failed` | info / warning | `AuthController` | `email` masqué, `status` (`passwords.user`, `passwords.throttled`) |
| `auth.password.reset_form` | info | `AuthController` (lien ouvert) | `email` masqué ; l'`url` partagée est masquée (token) |
| `auth.password.reset` / `.reset.failed` | info / warning | PasswordReset / `AuthController` | `user_id` ou `status` (`passwords.token`…) |
| `auth.unauthenticated` | info | `Authenticate`, `CheckRole` | `middleware`, `guards` ou `required_roles` |
| `auth.access.denied` | warning | `CheckRole` (403) | `required_roles` |
| `auth.access.granted` | debug | `CheckRole` | `required_roles` |
| `account_request.submitted` / `.submit.invalid` | info / notice | `AccountRequestController` | `account_request_id`, `email` masqué / `fields` en erreur |
| `account_request.approved` / `.rejected` | info | `AccountRequestController` | `account_request_id`, `created_user_id`, `has_notes` (jamais le contenu des notes ni le mot de passe temporaire) |
| `account_request.approve.skipped` / `.reject.skipped` | warning | `AccountRequestController` | `reason` (`already_processed`, `email_taken`) |
| `profile.email.changed` | info | `ProfileController` | `old_email`, `new_email` masqués |
| `profile.password.changed` / `.change_failed` | info / warning | `ProfileController` | `reason` (`wrong_current_password`, `invalid_new_password`) |

Les affichages de formulaires publics (`auth.login.form`, `auth.password.forgot_form`,
`account_request.form`, `account_request.success_page`) sont tracés en `debug`.

Alerte `auth_lockout` : `monitoring.auth_lockout.threshold` / `window_minutes`
(défaut : 10 blocages en 15 min).

Recherches utiles :

```bash
grep '"auth.login.failed"' storage/logs/security-*.log | grep -c invalid_credentials
grep 'auth.access.denied' storage/logs/security-$(date +%F).log
```

## Repas et menus (#25)

Toutes les actions des groupes `meals.*` et `week-menus.*` passent par le trait
`App\Http\Controllers\Concerns\GuardsMealMenuActions` :

- **écriture** (`guardAction`) : une exception inattendue est loguée en `error`
  (`<événement>.failed`, ex. `meal.store.failed`, `meal.inscription.store.failed`,
  `menu.rotation.toggle.failed`), comptée pour `error_spike`, et l'utilisateur revient sur
  le formulaire avec un message et la référence `(ref. <8 premiers caractères du request_id>)` ;
- **lecture** (`guardPage`) : même log (`meal.page.index.failed`, `menu.page.edit.failed`…)
  puis page/JSON 500 avec le request_id ;
- validation, 403, 404 et `abort()` ne sont jamais interceptés.

| Événement | Niveau | Canal | Quand |
|-----------|--------|-------|-------|
| `meal.created` / `meal.updated` / `meal.deleted` / `meal.toggled` | info | audit | Actions admin sur un repas (`fields` modifiés, nombre d'inscrits supprimés). |
| `meal.capacity.below_inscriptions` | warning | app | Capacité réduite sous le nombre de personnes inscrites. |
| `meal.inscription.created` / `.updated` / `.cancelled` | info | app | Inscriptions (nombre de places et d'invités, jamais leurs noms). |
| `meal.inscription.refused` | info | app | `reason` : `closed`, `already_registered`, `not_enough_places`, `not_registered`. |
| `broadcast.failed` | warning | app | Échec de `MealInscriptionChanged` (contexte `meal_id`, `action`), l'inscription est conservée. |
| `meal.close.completed` / `meal.close.missing` / `meal.close.failed` | info / warning / error | app | `meals:close` ; `missing` = aucun repas demain. Un échec fait échouer la commande. |
| `meal.generate.created` / `.completed` / `.failed` / `.incomplete` / `.invalid_count` | info / error / warning | app | `meals:generate` ; une date en échec n'arrête pas les suivantes et déclenche l'alerte `meal_generation_failure`. |
| `menu.week.*` (`created`, `updated`, `duplicated`, `moved`, `deleted`, `activated`, `dishes_updated`), `menu.cycle.reordered`, `menu.rotation.enabled` / `.disabled` | info | audit | Actions admin sur le cycle. |
| `menu.week.missing`, `menu.week.requested_missing`, `menu.week.source_missing`, `menu.week.without_slots`, `menu.week.foreign_dishes_ignored`, `menu.reference.missing` | warning | app | Semaine manquante ou données de menu incohérentes. |
| `menu.cycle.reorder_mismatch` | warning | app | Ordre reçu incohérent avec le cycle (contexte `order` et `cycle`). |
| `menu.rotation.anchor_lost` | warning | app | Ancre sortie du cycle : la rotation repart de la première semaine. |
| `menu.rotation.anchor_in_future` / `menu.rotation.empty_cycle` | warning | app | Cycle incohérent ; écrit au plus une fois par heure (calculé à chaque affichage). |
| `menu.rotation.failed` | error | app | Calcul de rotation en échec (`step`) : la semaine enregistrée reste affichée, alerte `menu_rotation_failure` (au plus une par 30 min). |

## Bulletin, dashboard, guide, nouveautés et QR codes (#27)

- **Dashboard** : chaque widget (repas, réservations, demandes, statistiques, bulletin,
  anniversaires, menu du jour) est chargé séparément. Un échec écrit
  `dashboard.widget.failed` (`widget`) puis `dashboard.degraded` (`failed_widgets`) ; la page
  s'affiche avec une valeur vide et un avertissement (`<x-degraded-notice>`).
- **Bulletin** : chaque écriture échouée écrit `bulletin.<action>.failed` dans `app`
  (`store`, `update`, `destroy`, `publish`, `service.*`, `activity.*`, `reflection.*`) avec
  les identifiants seulement (jamais titres ni textes), puis renvoie au formulaire avec la saisie.
  Création, publication/dépublication et suppression d'une édition sont tracées dans `audit`
  (`bulletin.created`, `bulletin.published`, `bulletin.unpublished`, `bulletin.deleted`).
  Lecture : `bulletin.current.failed` (page affichée sans bulletin), `bulletin.index.failed`,
  `bulletin.show.failed`, `bulletin.edit.failed`.
- **Services** : `BirthdayService` renvoie une collection vide (`birthday.week.failed`) ;
  `QRCodeService` journalise `qrcode.generate.failed` avec `size`, `mode` et `payload_length`
  (jamais l'URL encodée).
- **Guide / nouveautés** : `guide.load.failed` (guide vide + avertissement),
  `whats_new.seen.failed` (500 JSON avec `request_id`, ou retour avec message).
