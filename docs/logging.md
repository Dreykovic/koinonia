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

## 9. Erreurs front-end (JavaScript)

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

<!-- Runbook d'erreurs (diagnostic par symptôme) : à compléter (issue #32). -->
