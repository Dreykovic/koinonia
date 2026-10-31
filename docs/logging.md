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
L'`url` est journalisée sans query string et avec le token de `reset-password/{token}`
masqué (`AddRequestContext::safeUrl()`) : ni token, ni signature, ni e-mail en clair.

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
`exception` (la stack trace est conservée : `formatter_with.includeStacktraces` est forcé
dans `config/logging.php`, y compris avec `LOG_FORMATTER=JsonFormatter`). Le message est
passé par `LogMask::exceptionMessage()` : e-mails et hash masqués, et une `QueryException`
est journalisée sans ses valeurs liées (`error` = message du driver, `sql` avec ses `?`,
`sql_state`) ; l'exception est remplacée par une copie masquée qui garde la trace.

`logError()` est réservé aux `catch` qui **relancent** l'exception (le handler la reporte et
la compte). Un `catch` qui transforme l'erreur en redirection ou en page dégradée utilise
`logHandledError()` : même log, plus le compteur `error_spike` (sinon une panne de base
qui ne produit que des messages « Veuillez réessayer » n'alerterait jamais).

## 5. Quoi journaliser, quoi ne pas journaliser

### À journaliser

- Chaque **échec** d'une opération demandée par l'utilisateur (`error`, avec l'exception) et
  chaque **refus métier** (`warning`/`info` avec un champ `reason`) : on doit pouvoir répondre à
  « je n'ai pas pu réserver / m'inscrire » à partir des logs.
- Les **actions d'administration** (création, suppression, publication, décision) dans `audit`.
- Les **dégradations** gérées : broadcast en échec, widget du dashboard indisponible, cache
  absent, e-mail non envoyé.
- Les **identifiants** utiles à la corrélation (`reservation_id`, `meal_id`, `failed_email_id`,
  `job_id`…) et les **noms** des champs modifiés (`fields`), jamais leurs valeurs libres.
- Aucun `catch` ne doit avaler une erreur : soit on relance, soit `report($e)`, soit
  `logError()` / `logWarning()`. Dans un bloc qui ne doit jamais lever (handler, middleware de
  mesure, envoi d'alerte), utiliser `App\Support\SafeLog::failure()` (section 8).

### À ne jamais journaliser

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

- `LogMask::email()` renvoie `***` si le domaine n'est pas un nom d'hôte plausible
  (retours à la ligne, caractères de contrôle) : une valeur saisie ne peut pas forger de
  fausses lignes de log. Valider aussi l'entrée avant de la journaliser quand c'est possible.
- Texte libre venant de l'extérieur (réponse SMTP, message d'exception) : `LogMask::text()`
  (e-mails, hash, tokens de réinitialisation, paramètres sensibles d'URL).
- Les canaux fichiers ont un tap `App\Logging\MaskSensitiveData` : une `QueryException`
  reportée telle quelle (handler d'exceptions) est réécrite sans valeurs liées.
- Les e-mails d'alerte ne contiennent jamais de message d'exception : seulement la classe,
  le `request_id` ou l'identifiant du job ; le détail reste dans les logs locaux.

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
app(AlertService::class)->send('job_failed', 'Un job a échoué.', ['job' => $name]);

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
| `error_spike` | Exceptions reportées (5xx) via `Handler::register()`, 5xx explicites via `LogRequestDuration` (hors `/up`), échecs interceptés par un `catch` de contrôleur (`logHandledError()`, `GuardsMealMenuActions::guardAction()`), tous via `App\Support\ErrorSpike` | `ALERT_ERROR_SPIKE_THRESHOLD` / `_WINDOW` (10 en 5 min) |
| `mail_failure` | Échecs d'envoi d'e-mails | `ALERT_MAIL_FAILURE_THRESHOLD` / `_WINDOW` (3 en 15 min) |
| `broadcast_failure` | Échecs de `broadcastSafely()` (mise en file) et de chaque tentative d'un job `BroadcastEvent` dans le worker (appel Pusher, `QueueMonitoringServiceProvider`) | `ALERT_BROADCAST_FAILURE_THRESHOLD` / `_WINDOW` (5 en 10 min) |
| `job_failed` | Job en échec définitif (`QueueMonitoringServiceProvider`) | `ALERT_QUEUE_FAILURE` (true/false) |
| `scheduled_task_failed` | Tâche planifiée en échec (`ScheduleMonitor`, hook `onFailure`) | aucun (à chaque échec) |
| `scheduler_stale` | `SchedulerHeartbeat::isStale()`, vérifié par `LogRequestDuration` (toutes les 5 min) | `MONITORING_HEARTBEAT_MAX_AGE` (5 min ; > intervalle du cron, voir deploy-ovh.md §6) |
| `auth_lockout` | Blocages de connexion (`LogAuthenticationEvents`) | `ALERT_AUTH_LOCKOUT_THRESHOLD` / `_WINDOW` (10 en 15 min) |
| `menu_rotation_failure` | Calcul de rotation des menus en échec (`MenuRotation`) | constante (1 en 30 min) |
| `meal_generation_failure` | `meals:generate` en échec pour au moins une date | aucun (à chaque échec) |

Toutes ces règles se testent avec `php artisan monitoring:test-alert [règle]`. Diagnostic et
correction de chaque règle : runbook en section 20.

Seuils de lenteur : `MONITORING_SLOW_QUERY_MS` (500 ms), `MONITORING_SLOW_REQUEST_MS` (2000 ms).

## 8. Helpers disponibles

| Helper | Rôle |
|--------|------|
| `App\Traits\LogsErrors` | `logError($event, $e, $context, $channel)` (exception relancée), `logHandledError(...)` (exception interceptée : log + `error_spike`), `logWarning(...)`, `logInfo(...)`. |
| `App\Support\ErrorSpike` | `count($exceptionOrStatus)` : compteur `error_spike`, sans message d'exception dans l'alerte, ne lève jamais. |
| `App\Traits\BroadcastsSafely` | `broadcastSafely($event, $context, $toOthers)` : diffuse sans jamais lever d'exception, log `broadcast.failed` + compteur `broadcast_failure`. Retourne `true` si diffusé. |
| `App\Services\AlertService` | `send()` / `hit()`, voir ci-dessus. |
| `App\Support\SchedulerHeartbeat` | `beat()` (à appeler par le scheduler), `lastBeat()`, `isStale()`. Stocké dans le cache par défaut : le cron et le web doivent partager le même store (`CACHE_DRIVER=file` sur OVH). |
| `App\Support\LogMask` | `email()` : masque une adresse ; `text()` : masque e-mails, hash et tokens d'un texte libre ; `exceptionMessage()` / `exceptionContext()` : message et contexte d'exception sans données liées. |
| `App\Support\SafeLog` | `failure($event, $e, $context, $channel)` : `warning` avec l'exception, sans jamais lever ; si le canal lui-même échoue (`storage/logs` non inscriptible), écrit dans le log d'erreurs PHP (`error_log`). Pour les `catch` qui protègent une opération secondaire (alerte, métrique). Événements : `alert.dispatch.failed` (`rule`), `monitoring.request_metrics.failed`, `request.context.user_unavailable` (utilisateur illisible, base en panne : le contexte est partagé sans `user_id`), `mail.failed_email.subject_unavailable` / `.payload_unavailable` (`mailable`). |

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
| `auth.password.reset_form` | info | `AuthController` (lien ouvert) | `email` masqué ; l'`url` partagée n'a ni token ni query string |
| `auth.password.reset` / `.reset.failed` | info / warning | PasswordReset / `AuthController` | `user_id` ou `status` (`passwords.token`…) |
| `auth.unauthenticated` | info | `Authenticate`, `CheckRole` | `middleware`, `guards` ou `required_roles` |
| `auth.access.denied` | warning | `CheckRole` (403) | `required_roles` |
| `auth.access.granted` | info | `CheckRole` | `required_roles` |
| `account_request.submitted` / `.submit.invalid` | info / notice | `AccountRequestController` | `account_request_id`, `email` masqué / `fields` en erreur |
| `account_request.approved` / `.rejected` | info | `AccountRequestController` | `account_request_id`, `created_user_id`, `has_notes` (jamais le contenu des notes ni le mot de passe temporaire) |
| `account_request.approve.skipped` / `.reject.skipped` | warning | `AccountRequestController` | `reason` (`already_processed`, `email_taken`) |
| `profile.email.changed` | info | `ProfileController` | `old_email`, `new_email` masqués |
| `profile.password.changed` / `.change_failed` | info / warning | `ProfileController` | `reason` (`wrong_current_password`, `invalid_new_password`) |

Les affichages de formulaires publics (`auth.login.form`, `auth.password.forgot_form`,
`account_request.form`, `account_request.success_page`) sont tracés en `info`. Le canal
`security` n'est jamais plus strict que `info` (même avec `LOG_LEVEL=warning`) : chaque accès
invité ou `role:*` laisse une trace en production.

Alerte `auth_lockout` : `monitoring.auth_lockout.threshold` / `window_minutes`
(défaut : 10 blocages en 15 min).

Recherches utiles :

```bash
grep '"auth.login.failed"' storage/logs/security-*.log | grep -c invalid_credentials
grep 'auth.access.denied' storage/logs/security-$(date +%F).log
```

## 10. Utilisateurs, communautés et demandes (issue #24)

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

## 11. Repas et menus (#25)

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

## 12. Véhicules, réservations et lieux de réparation

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

## 13. Bulletin, dashboard, guide, nouveautés et QR codes (#27)

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

## 14. Emails (issue #28)

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

## 15. File d'attente, commandes et scheduler (#29)

Tout est écrit dans le canal `queue`.

| Événement | Niveau | Source |
|-----------|--------|--------|
| `queue.job.failed` | error | Job définitivement en échec (= ligne dans `failed_jobs`), via `Queue::failing` dans `App\Providers\QueueMonitoringServiceProvider`. Contexte : `job`, `job_id`, `uuid`, `connection`, `queue`, `attempts`, `max_tries`, exception. Jamais le payload. Alerte `job_failed` (désactivable par `ALERT_QUEUE_FAILURE=false`). |
| `queue.job.exception` | warning | Tentative en échec qui sera retentée. |
| `queue.job.timed_out` | warning | Job interrompu par `--timeout`. |
| `queue.worker.stopped` | warning | Worker arrêté avec un statut non nul (mémoire, timeout). |
| `queue.failed_jobs.pending` | warning | `monitoring:failed-jobs` (tous les jours à 07:00) : jobs restant dans `failed_jobs` (`total`, `last_24h`), à traiter par `queue:retry` / `queue:forget`. |
| `command.started` / `command.finished` | info (warning si code ≠ 0) | Commandes Artisan de l'application (trait `App\Console\Concerns\MonitorsExecution`) : `command`, `arguments`, `exit_code`, `duration_ms`, `metrics` (compteurs métier). |
| `command.failed` | error | Exception dans une commande : log avec l'exception, la commande retourne `1`. |
| `schedule.task.failed` | error | Tâche planifiée terminée avec un code ≠ 0 (hook `onFailure` posé sur **toutes** les tâches par `App\Console\Scheduling\ScheduleMonitor`) : `task`, `exit_code`, `expression`, fin de la sortie (`output`, 2000 caractères). Alerte `scheduled_task_failed`. |

- Nouvelle commande : `use MonitorsExecution;` puis `return $this->monitored(function () { $this->metrics = [...]; return self::SUCCESS; });`.
- Nouvelle tâche planifiée : la déclarer dans `App\Console\Kernel::schedule()` **avant** la boucle `ScheduleMonitor::watch()`.
- La sortie des tâches est capturée dans `storage/logs/schedule-<hash>.log` (écrasée à chaque exécution).
- Heartbeat : la closure `monitoring:heartbeat` appelle `SchedulerHeartbeat::beat()` chaque minute.

## 16. Erreurs front-end (JavaScript)

Le navigateur remonte ses erreurs à `POST /log/client` (route `log.client`,
`App\Http\Controllers\ClientErrorController`), journalisées dans `app` :

| Événement | Niveau | Origine |
|-----------|--------|---------|
| `client.js.error` | `error` | `window.onerror` (exception non gérée) |
| `client.js.unhandled_rejection` | `error` | promesse rejetée sans `catch` |
| `client.network.failed` | `warning` | échec réseau (status 0) ou 5xx d'un appel fetch / Axios / jQuery |
| `client.broadcast.connection_lost` | `warning` | connexion Pusher `unavailable`, `failed`, `disconnected` ou `error` |

Contexte : `error`, `source`, `line`, `col`, `stack` (tableau de lignes), `page_url`,
`http_method`, `http_status`, `request_url`, `failed_request_id` (request_id de la
réponse 5xx, pour retrouver l'exception serveur), `state`, `app_version` (serveur),
`client_version` (meta de la page : différente si l'onglet date d'avant un déploiement),
`user_agent`, plus le contexte partagé (`user_id`, `roles`…).

- **Code** : `resources/js/monitoring/` (chargé en premier par `app.js` ; `error-reporting.js`
  seul sur `layouts/guest`). Version exposée par `<meta name="app-version">`
  (`App\Support\AppVersion` : `config('app.version')` sinon la dernière version de `ReleaseNotes`).
- **Anti-flood** : côté client, une même erreur une seule fois par page, 5 envois/minute et
  20 par page ; côté serveur `throttle:20,1`, CSRF, validation stricte (422 sans log), champs
  tronqués, mis sur une ligne (pas d'injection de fausses lignes) et expurgés (token de
  `reset-password/…`, paramètres `token`/`signature`/`password`/`email`…, e-mails masqués).
- **Non remontés** : réponses 4xx (validation, 419, 429 : attendues), requêtes annulées,
  échecs hors ligne, `Script error.` opaque, scripts d'extensions, boucle ResizeObserver.
- **Source maps** : désactivées (`vite.config.js`). Sans outil externe pour les recevoir,
  `hidden` les publierait quand même dans `public/build` (servi et commité). Les stacks
  pointent donc vers le code minifié ; `client_version` + `page_url` situent la release.
- **Affichage des erreurs** : `window.AppErrors.message(xhr|error, fallback)` donne le message
  français (avec `réf. <request_id>` pour un 5xx) et `AppErrors.show(message)` l'affiche
  via `Toast` (layout app) ou SweetAlert (pages autonomes), texte échappé.
- **Temps réel** : en cas de coupure, notice discrète après 3 s ; Pusher retente et se
  réabonne seul ; au retour, l'événement `window` `broadcast:reconnected` permet de
  recharger les données (utilisé par le planning véhicules).

Retrouver les erreurs d'un utilisateur : `grep '"user_id":42' storage/logs/app-*.log | grep client.`

## 17. Observabilité (health check, lenteurs, N+1)

- **`GET /up`** (route `health`, sans authentification ni session, `throttle:30,1` par IP) : JSON
  `{"status": "ok|degraded", "checks": {"database", "cache", "queue", "storage", "scheduler"}}`,
  **200** si tout est `ok`, **503** sinon. Publiquement, chaque vérification ne donne que son
  `status` ; avec `?token=` égal à `MONITORING_HEALTH_TOKEN`, `queue` ajoute `pending` /
  `failed` et `scheduler` ajoute `last_beat`. Aucun message d'exception dans la réponse : le
  détail est journalisé (`health.check.failed`, canal `app`), au plus une fois par
  vérification toutes les 5 minutes ; si la base est en panne, `queue` n'est pas testée
  (une seule ligne). Un 503 de `/up` ne compte pas pour `error_spike`.
- **Requêtes SQL lentes** (`App\Providers\ObservabilityServiceProvider`) : `db.query.slow`
  en `warning` au-delà de `MONITORING_SLOW_QUERY_MS` (SQL avec placeholders, sans valeurs
  liées ; seuil négatif = désactivé).
- **N+1** : hors production, `Model::preventLazyLoading()` est actif mais une violation est
  journalisée (`db.lazy_loading`, modèle, relation, `location` dans l'application) au lieu
  de lever une exception ; une ligne par modèle + relation et par processus. Rien en production.
- **Temps de réponse** (`App\Http\Middleware\LogRequestDuration`, juste après
  `AddRequestContext` dans les groupes `web` et `api`) : `http.request.slow` en `warning`
  au-delà de `MONITORING_SLOW_REQUEST_MS`, avec `duration_ms` et `status`. Les réponses 5xx
  renvoyées sans exception reportée comptent pour `error_spike` (les exceptions reportées
  sont déjà comptées par le handler : pas de double comptage).
- **Scheduler inactif** : faute de superviseur externe, ce même middleware vérifie au plus
  toutes les 5 minutes (après l'envoi de la réponse) `SchedulerHeartbeat::isStale()` et
  envoie l'alerte `scheduler_stale`. Sans aucun battement (installation neuve, `cache:clear`
  / `optimize:clear` après un déploiement), le cron dispose de `MONITORING_HEARTBEAT_MAX_AGE`
  minutes à partir du premier constat avant l'alerte et le 503 de `/up` : pas de fausse
  alerte au déploiement. Hors production, jamais d'alerte sans battement (dev sans cron).
- **Alerte de test** : `php artisan monitoring:test-alert` envoie une alerte `[TEST]` pour
  chaque règle de la section 7 (liste dans `MonitoringTestAlertCommand::RULES`) en ignorant
  le throttle ; `php artisan monitoring:test-alert mail_failure` pour une seule règle.

## 18. Consultation des journaux (#35)

Page *Administration > Journaux* (`admin.logs.index`, rôle `admin`, lecture seule) :
`App\Http\Controllers\Admin\LogController` + `App\Services\LogReader`.

- **Fichiers lus** : `app`, `security`, `mail`, `queue`, `audit` (`<canal>-AAAA-MM-JJ.log`) et
  `laravel` (`laravel.log` ou `laravel-AAAA-MM-JJ.log`). Le nom est reconstruit à partir d'un
  canal et d'une date validés, vérifié par `LogReader::FILE_PATTERN` puis confiné au répertoire
  (`realpath`, pas de lien symbolique vers l'extérieur). Les autres fichiers (`schedule-*.log`…)
  ne sont jamais lus.
- **Lecture en flux** : ligne par ligne (`fgets`), une entrée multi-lignes (stack trace) est
  regroupée jusqu'au début de l'entrée suivante. Formats ligne (`[date] env.LEVEL: message
  {context} {extra}`) et JSON (`LOG_FORMATTER`), éventuellement mélangés dans un même fichier.
- **Pagination** (50 par page, plus récentes d'abord) : seuls les fichiers de la plage de dates
  sont ouverts (date du nom, ou date de modification pour `laravel.log`) ; un tas borné à
  `page × 50` garde la position (fichier, offset) des entrées les plus récentes, puis les
  entrées de la page sont relues par `fseek`. 200 pages au plus : affiner les filtres au-delà.
- **Filtres** : plage date/heure (24 dernières heures par défaut, sans limite quand un
  request_id est saisi), niveau minimum, canal, utilisateur (id, e-mail exact ou nom partiel),
  route (sous-chaîne), texte libre (sur la ligne brute), request_id (complet ou 8 premiers
  caractères, ex. la référence `ref. abcd1234` d'un message flash). Le request_id correspond
  aussi à `origin_request_id` (job en file) et `failed_request_id` (erreur front-end).
- **Requête** (`admin.logs.request`) : toutes les lignes d'un request_id, tous canaux, dans
  l'ordre chronologique (fichiers de la veille au lendemain quand la date est connue).
- **Détail** (`admin.logs.show`, `channel` + `date` + `offset`) : contexte complet en JSON
  indenté, exception et stack trace repliable.
- **Audit** : chaque consultation écrit `admin.logs.viewed` (`filters`, e-mail masqué,
  `page`, `results`), `admin.logs.entry_viewed` ou `admin.logs.request_viewed`
  (`searched_request_id`) ; des filtres invalides écrivent `admin.logs.invalid_filters` (warning).

Pour retrouver l'erreur d'un utilisateur : coller la référence de la page 500 dans le filtre
*Request id*, ou filtrer par utilisateur et heure approximative avec le niveau `error`.

## 19. Procédure d'investigation à partir d'un request_id

Chaque requête HTTP reçoit un identifiant (`X-Request-Id`, UUID) présent dans **toutes** ses
lignes de log, dans tous les canaux. C'est le point d'entrée de toute enquête.

1. **Récupérer l'identifiant** auprès de l'utilisateur :
   - page d'erreur (403, 404, 419, 429, 500, 503…) : « Référence de l'erreur à communiquer
     au support : `<request_id>` » ;
   - message flash d'échec (repas, menus…) : `(ref. abcd1234)`, les 8 premiers caractères ;
   - message JavaScript d'un 5xx (`AppErrors.message`) : `réf. <request_id>` ;
   - réponse JSON d'erreur : champ `request_id` ; sinon l'en-tête `X-Request-Id` (outils
     de développement du navigateur, onglet Réseau).
   Sans référence : filtrer par utilisateur et heure approximative avec le niveau `error`.
2. **Page *Administration > Journaux*** (`/admin/logs`, admin) : coller la référence (complète
   ou 8 caractères) dans le filtre *Request id*. Le filtre ignore la plage de dates et retrouve
   aussi les jobs déclenchés par la requête (`origin_request_id`) et les erreurs front-end qui
   la citent (`failed_request_id`).
3. **Chronologie de la requête** (`/admin/logs/request/<request_id>`, bouton *Toute la requête* du
   détail d'une entrée) : toutes les lignes dans l'ordre, tous canaux confondus. Lire de haut en bas :
   contexte (`user_id`, `roles`, `route`, `method`, `url`), événements métier, puis l'erreur.
   Le détail d'une entrée donne le contexte complet, `exception_class`, `location`
   (fichier:ligne) et la stack trace repliable.
4. **Remonter la chaîne** selon ce qu'on y trouve :
   - `mail.send.failed` → `failed_email_id` → *Administration > Emails échoués* (`#id`) ;
   - `queue.job.failed` → `job_id`/`uuid` → table `failed_jobs`, `php artisan queue:failed` ;
   - `broadcast.failed` → état Pusher (tableau de bord Pusher), le planning est retombé sur le
     polling `/api/v1` ;
   - `client.network.failed` avec `failed_request_id` → recommencer à l'étape 2 avec cet id ;
   - `db.query.slow` / `http.request.slow` → lenteur, pas une erreur : voir `duration_ms`.
5. **Fichiers sur le serveur** (SSH, si la page admin est elle-même en panne) :

   ```bash
   cd ~/koinonia/storage/logs
   grep -l '<request_id>' *.log                       # quels canaux sont concernés
   grep -h '<request_id>' *-$(date +%F).log laravel*.log | less
   grep -h '"exception_class"' app-$(date +%F).log | tail -n 20
   ```

   En JSON (`LOG_FORMATTER`), une ligne = une entrée : `grep … | python3 -m json.tool` pour la
   lire. Les exceptions non gérées sont dans `laravel.log` (stack par défaut), les autres dans
   leur canal. Ne jamais copier un extrait de log contenant le `.env`, un mot de passe ou un
   e-mail en clair dans un ticket public.
6. **Reproduire / corriger**, puis vérifier qu'aucune nouvelle ligne `error` n'apparaît pour
   la même `route` (filtre *Route* de la page Journaux).

Une page 500 **sans** référence ni ligne dans les logs signale en général un `storage/logs`
non inscriptible ou un cache de configuration SSH : voir `docs/deploy-ovh.md`, section Dépannage.

## 20. Runbook des alertes

Une alerte arrive par e-mail (sujet avec la règle) et laisse une ligne `critical`
`alert.<règle>` dans `app-*.log` avec le contexte. Les e-mails d'une même règle sont limités à
un toutes les `ALERT_THROTTLE_MINUTES` : consulter le log pour le nombre réel d'occurrences.

Recherche commune : `grep '"alert.' storage/logs/app-$(date +%F).log`, ou page Journaux,
niveau `critical`, canal `app`.

| Règle | Signification | Vérifier | Corriger |
|-------|---------------|----------|----------|
| `error_spike` | Au moins `ALERT_ERROR_SPIKE_THRESHOLD` erreurs serveur (exceptions reportées, 5xx explicites, échecs interceptés par les `catch` des contrôleurs et `guardAction`) en `ALERT_ERROR_SPIKE_WINDOW` min. | `last_exception` / `last_status` / `request_id` dans l'alerte (jamais le message) ; rechercher le `request_id` dans la page Journaux ; page Journaux, niveau `error`, dernière heure ; `laravel.log`. Grouper par `route` et `exception_class`. | Cause commune la plus probable : base indisponible (`/up` → `database`), disque plein, déploiement partiel (`composer install`, `migrate --force`). Sinon corriger le bug de la route en cause. |
| `job_failed` | Un job est en échec définitif (après `--tries=3`) et a rejoint `failed_jobs`. | `queue.job.failed` dans `queue-*.log` (`job`, `uuid`, exception) ; `php artisan queue:failed`. Pour un mailable : *Emails échoués*. | Corriger la cause, puis `php artisan queue:retry <uuid>` (ou `all`) ; `queue:forget <uuid>` si le job est obsolète. `chmod -R a+rwX storage bootstrap/cache` après. |
| `scheduled_task_failed` | Une tâche planifiée s'est terminée avec un code ≠ 0. | `schedule.task.failed` dans `queue-*.log` (`task`, `exit_code`, fin de `output`) ; `command.failed` juste avant ; `storage/logs/schedule-*.log`. | Relancer la commande à la main en SSH (ex. `php artisan meals:close`) pour voir l'erreur, corriger, puis `chmod`. |
| `scheduler_stale` | Pas de battement de `schedule:run` depuis plus de `MONITORING_HEARTBEAT_MAX_AGE` min : **ni file d'attente, ni tâches planifiées**. | `/up` → `checks.scheduler.last_beat` ; Manager OVH → *Tâches automatisées (CRON)* (tâche active, dernière exécution) ; `php artisan schedule:list`. | Réactiver ou recréer le cron (`koinonia/artisan schedule:run`, chaque minute, même version PHP). Après un `cache:clear`, le battement est effacé mais l'alerte n'est envoyée qu'après `MONITORING_HEARTBEAT_MAX_AGE` minutes sans nouveau battement : si elle arrive, le cron ne tourne vraiment pas. Vérifier que web et cron partagent le cache (`CACHE_DRIVER=file`). |
| `mail_failure` | Au moins `ALERT_MAIL_FAILURE_THRESHOLD` envois en échec en `ALERT_MAIL_FAILURE_WINDOW` min. | `mail.send.failed` dans `mail-*.log` (`exception_class`, `error`) ; *Emails échoués*. | SMTP : identifiants `MAIL_*`, mot de passe d'application expiré, quota du fournisseur, port bloqué. Une fois réparé, bouton **Renvoyer** sur chaque entrée. Note : si le SMTP est en panne, l'e-mail d'alerte lui-même échoue (`alert.mail.failed` dans `mail-*.log`) : surveiller aussi le log. |
| `broadcast_failure` | Au moins `ALERT_BROADCAST_FAILURE_THRESHOLD` diffusions temps réel en échec en `ALERT_BROADCAST_FAILURE_WINDOW` min. Les actions aboutissent, seul le temps réel est dégradé. | `broadcast.failed` dans `app-*.log` (`event`, `error`, `job_uuid` pour un échec dans le worker) ; tableau de bord Pusher (quota, application désactivée) ; `client.broadcast.connection_lost` côté navigateur. | Vérifier `PUSHER_*` et `BROADCAST_DRIVER=pusher` ; la clé publique est lue à l'exécution (`<meta name="pusher-key">` rendue depuis `PUSHER_APP_KEY`) : pas de rebuild de `public/build` nécessaire. Le polling `/api/v1` assure le service en attendant. |
| `auth_lockout` | Au moins `ALERT_AUTH_LOCKOUT_THRESHOLD` blocages de connexion (5 échecs e-mail+IP) en `ALERT_AUTH_LOCKOUT_WINDOW` min : tentative de force brute probable. | `auth.lockout` / `auth.login.failed` dans `security-*.log` : `ip`, `email` masqué, `reason`. | Une seule IP : la bloquer (`.htaccess` `Require not ip …`). Un seul compte visé : prévenir le membre, réinitialiser son mot de passe. Faux positif (membre qui a oublié son mot de passe) : rien à faire. |
| `menu_rotation_failure` | Le calcul de la semaine de menu affichée a échoué ; la semaine enregistrée reste affichée (au plus une alerte par 30 min). | `menu.rotation.failed` dans `app-*.log` (`step`, exception) ; `menu.rotation.*` en warning juste avant. | *Gestion Menu* (`/week-menu/admin/edit`) : vérifier le cycle (semaines actives, ordre, semaine d'ancrage), réenregistrer l'ordre ou désactiver puis réactiver la rotation. |
| `meal_generation_failure` | `meals:generate` (dimanche 00:00) n'a pas pu créer le repas d'au moins une date. | `meal.generate.failed` / `.incomplete` dans `app-*.log` (`failed_dates`). | Corriger la cause (base, données), puis relancer `php artisan meals:generate 4` (les dates existantes sont ignorées) ou créer le repas à la main dans *Repas*. |

Lignes liées à la chaîne d'alerte elle-même :

- `alert.mail.failed` (`mail`, error) : l'e-mail d'alerte n'est pas parti (SMTP) ; l'alerte
  reste dans le log. Voir `mail_failure`.
- `alert.no_recipients` (`app`, warning) : ni `ALERT_MAIL_TO` ni administrateur actif. Définir
  `ALERT_MAIL_TO`.
- `alert.recipients.failed` (`app`, error) : la liste des admins n'a pas pu être lue (base en
  panne) ; définir `ALERT_MAIL_TO` pour ne pas dépendre de la base.
- `alert.dispatch.failed` (`app`, warning, `rule`) : le déclenchement d'une alerte a levé une
  exception (cache indisponible…) ; l'erreur d'origine est quand même reportée.

**Envoyer une alerte de test** (après chaque déploiement, ou après un changement de SMTP ou de
destinataires) :

```bash
php artisan monitoring:test-alert                 # une alerte [TEST] par règle
php artisan monitoring:test-alert scheduler_stale  # une seule règle
chmod -R a+rwX storage bootstrap/cache
```

Le tableau de sortie donne `envoyée` ou `échec` par règle ; en cas d'échec, lire
`storage/logs/mail-*.log` (`alert.mail.failed`). Le test ignore puis libère le throttle : il
ne masque pas une vraie alerte qui suivrait.

## 21. Tests et garde-fous (CI)

La CI (`.github/workflows/tests.yml`, MySQL 8, à chaque pull request et push sur `main`) lance
toute la suite. Tests liés au logging :

| Test | Vérifie |
|------|---------|
| `LoggingCoverageTest` | Chaque route appartient au groupe `web` ou `api` et reçoit `AddRequestContext` (via `Router::gatherRouteMiddleware`). Exceptions justifiées dans `WITHOUT_CONTEXT` (routes de développement `_ignition/*` uniquement). `SafeLog` ne lève jamais. |
| `ErrorPagesTest` | Une réponse par type d'erreur (401, 403, 404, 405, 410/4xx, 419, 422, 429, 500, 502/5xx, 503), en HTML et en JSON, avec request_id ; exception reportée avec son contexte ; bruit 4xx non reporté ; échec d'alerte tracé. |
| `SecurityLoggingTest`, `VehicleReservationLoggingTest`, `MealMenuLoggingTest`, `BulletinDashboardLoggingTest`, `MailFailureLoggingTest`, `QueueConsoleMonitoringTest`… | Assertions sur les logs des routes critiques : `auth.login.failed`, `reservation.conflict`, `meal.inscription.*`, `bulletin.published`, `mail.send.failed`, `queue.job.failed`. |

Une nouvelle route hors `web`/`api` fait échouer `LoggingCoverageTest` : l'ajouter à un groupe,
ou justifier l'exception dans `WITHOUT_CONTEXT`.
