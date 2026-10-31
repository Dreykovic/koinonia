# Déploiement sur OVHcloud (Hébergement Web Cloud mutualisé)

Guide pour mettre Koinonia (Laravel 10, PHP ≥ 8.1, MySQL) en production sur un hébergement mutualisé OVHcloud, avec reprise des données existantes. Pour la bascule de domaine et le plan de retour, voir aussi [domain-migration.md](domain-migration.md).

Testé sur une offre **Agency** (PHP 8.2 en ligne de commande, `git` et `composer` disponibles). Les offres Starter n'ont ni SSH ni cron et ne conviennent pas.

## Particularités de l'application

- **Pas de worker permanent** : la file d'attente est traitée par le scheduler (`queue:work --stop-when-empty` chaque minute, voir `app/Console/Kernel.php`). Un **seul cron** `schedule:run` suffit, pas besoin de Supervisor.
- **Temps réel** : Pusher (service externe). Aucun daemon WebSocket à héberger.
- **Assets Vite** : `public/build` est versionné dans git. Pas besoin de Node sur le serveur.
- **Tâches planifiées** : fermeture des repas (samedi 18h), génération des repas (dimanche 0h), nettoyage des notifications (3h), rapport des jobs en échec (7h), battement de cœur du scheduler (chaque minute).
- **Logs et alertes 100 % locaux** : fichiers dans `storage/logs/` et e-mails d'alerte envoyés par le SMTP de l'application (aucun service externe). Conventions, investigation et runbook : [logging.md](logging.md).

## Pièges propres à l'hébergement mutualisé OVH

Lire cette section avant de commencer : chacun de ces points a causé une panne lors du premier déploiement.

1. **Deux utilisateurs, deux chemins.** Le serveur web tourne sous l'utilisateur principal (`/home/PRINCIPAL/koinonia`), alors que votre connexion SSH peut être un utilisateur secondaire (`/home/PRINCIPAL-SECONDAIRE/koinonia`). Ce sont les mêmes fichiers vus par deux chemins différents.
2. **Ne jamais lancer `php artisan config:cache` en SSH.** Le cache enregistre le chemin vu par SSH ; le serveur web ne peut pas l'utiliser, Laravel n'arrive plus à écrire ses logs et **chaque requête renvoie une erreur 500 au corps vide**. Laisser la configuration non mise en cache (le coût est négligeable à cette échelle). `route:cache` et `view:cache` ne contiennent pas de chemins absolus.
3. **Permissions.** Les fichiers créés par l'utilisateur SSH ne sont pas inscriptibles par le serveur web. Après installation, et après toute commande `artisan` qui écrit des fichiers :
   ```bash
   chmod -R a+rwX storage bootstrap/cache
   ```
4. **Le DNS réellement utilisé n'est pas forcément celui d'OVH.** Si le domaine est servi par d'autres serveurs de noms, les enregistrements ajoutés dans la zone DNS d'OVH n'ont **aucun effet**. Voir la section « DNS et SSL ».
5. **Ne pas changer la version PHP globale** de l'hébergement s'il porte d'autres sites : régler la version par site (Multisite).

## 0. Prérequis

- [ ] Offre Web Cloud avec accès SSH, base MySQL et plusieurs sites (multisite).
- [ ] Domaine ou sous-domaine : savoir **qui gère la zone DNS réellement servie** (voir « DNS et SSL »).
- [ ] Identifiants Pusher de production.
- [ ] Un expéditeur d'e-mails (voir `MAIL_*` ci-dessous) : l'option « Adresses e-mail » de l'hébergement peut être désactivée.

## 1. Configuration de l'hébergement (Manager OVH)

1. **Multisite** : ajouter le domaine avec le **dossier racine `koinonia/public`**. `.env`, `app/` et `vendor/` ne sont ainsi pas exposés sur le web. Choisir la version **PHP 8.2 ou supérieure** pour ce site (pas pour tout l'hébergement), environnement **Production**.
2. **SSL** : activer le certificat Let's Encrypt pour le site. Il ne peut être émis que lorsque le nom se résout publiquement (voir « DNS et SSL »). Si besoin, utiliser **Régénérer le certificat SSL** ensuite.
3. **Base MySQL** : créer une base **neuve et vide** (*Bases de données* → *Créer*). Noter l'hôte (`NOM-BASE.mysql.db`, généralement le nom de la base suivi de `.mysql.db`), le nom et l'utilisateur. Ne jamais coller le mot de passe dans un chat ou un ticket : il apparaît aussi dans les traces d'erreur.
4. **SSH** : vérifier que l'accès SSH est activé pour l'utilisateur (onglet *FTP - SSH*) :
   ```bash
   ssh UTILISATEUR@ssh.clusterNNN.hosting.ovh.net
   ```

## 2. Envoyer le code

Par SSH sur l'hébergement. Ne jamais cloner dans un dossier existant (par exemple l'ancienne version du site) : prendre un dossier neuf.

```bash
cd ~
ls                      # vérifier que "koinonia" n'existe pas déjà
git clone https://github.com/Dreykovic/koinonia.git koinonia
cd koinonia
php -v                  # la CLI doit être en PHP >= 8.1
composer install --no-dev --optimize-autoloader
```

## 3. Fichier `.env` de production

```bash
cp .env.example .env
nano .env               # jamais commité
```

```dotenv
APP_NAME=Koinonia
APP_ENV=production
APP_DEBUG=false
APP_URL=https://NOUVEAU          # avec https://, sinon les liens générés sont faux

# Journaux (docs/logging.md) : fichiers quotidiens, JSON, 30 jours
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_FORMATTER=Monolog\Formatter\JsonFormatter
LOG_DAILY_DAYS=30
LOG_LEVEL=info                   # info : garde les événements métier et l'audit ; warning pour réduire

# Alertes locales : ligne critical + e-mail aux admins via MAIL_*
ALERT_ENABLED=true
ALERT_MAIL_TO=admin@NOUVEAU      # vide = administrateurs actifs ; recommandé : ne dépend pas de la base
ALERT_THROTTLE_MINUTES=30
ALERT_ERROR_SPIKE_THRESHOLD=10
ALERT_ERROR_SPIKE_WINDOW=5
ALERT_MAIL_FAILURE_THRESHOLD=3
ALERT_MAIL_FAILURE_WINDOW=15
ALERT_BROADCAST_FAILURE_THRESHOLD=5
ALERT_BROADCAST_FAILURE_WINDOW=10
ALERT_AUTH_LOCKOUT_THRESHOLD=10
ALERT_AUTH_LOCKOUT_WINDOW=15
ALERT_QUEUE_FAILURE=true

# Seuils de surveillance
MONITORING_SLOW_QUERY_MS=500
MONITORING_SLOW_REQUEST_MS=2000
MONITORING_HEARTBEAT_MAX_AGE=5   # minutes sans schedule:run avant l'alerte scheduler_stale

DB_CONNECTION=mysql
DB_HOST=NOM-BASE.mysql.db
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

BROADCAST_DRIVER=pusher
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true       # nécessite HTTPS fonctionnel pour pouvoir se connecter
TRUSTED_PROXIES=*                # OVH place un proxy/CDN devant PHP

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=...
MAIL_TIMEOUT=10
MAIL_FROM_ADDRESS="contact@NOUVEAU"
MAIL_FROM_NAME="${APP_NAME}"

PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=...

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
```

Notes sur les e-mails :
- Le serveur SMTP d'OVH (`ssl0.ovh.net`, port 465, SSL) n'est utilisable que si des boîtes e-mail existent (MX Plan, Email Pro).
- Avec Gmail, il faut un **mot de passe d'application** (validation en deux étapes requise), et les messages envoyés « depuis » une adresse Gmail par un autre serveur finissent souvent en spam. Un expéditeur sur votre propre domaine est préférable.

> `VITE_PUSHER_*` est intégré au bundle au moment du build. Si la clé Pusher de production diffère de celle utilisée à la compilation, relancer `npm run build` en local avec la bonne valeur, committer `public/build` et redéployer.

Puis :

```bash
chmod 600 .env
php artisan key:generate
chmod -R a+rwX storage bootstrap/cache
php artisan migrate:status      # doit lister les migrations, sans erreur de connexion
```

## 4. Reprise des données

**Sur l'ancien serveur / en local** :

```bash
mysqldump --single-transaction --no-tablespaces -u USER -p BASE > backup-$(date +%F).sql
tar czf storage-$(date +%F).tgz storage/app
```

Depuis phpMyAdmin, l'onglet *Exporter* (format SQL, méthode rapide) donne le même résultat.

**Sur l'hébergement OVH**, dans la base **neuve et vide** uniquement :

- Par phpMyAdmin : *Manager* → *Bases de données* → `...` → *Accéder à phpMyAdmin*, cliquer la base, onglet *Importer*, choisir le fichier `.sql` ou `.sql.gz`. Si le fichier dépasse la limite affichée, utiliser SSH.
- Par SSH (après `scp` du fichier) :
  ```bash
  mysql -h NOM-BASE.mysql.db -u USER -p BASE < ~/backup-AAAA-MM-JJ.sql
  ```

Puis :

```bash
tar xzf storage-AAAA-MM-JJ.tgz            # depuis ~/koinonia
php artisan migrate --force               # applique les migrations manquantes
php artisan storage:link
chmod -R a+rwX storage bootstrap/cache
```

Erreur `Unknown collation: utf8mb4_uca1400_ai_ci` : la source est un MariaDB plus récent que MySQL chez OVH ; remplacer la collation dans le fichier `.sql` par `utf8mb4_unicode_ci` avant l'import.

Pour une base vide (première installation) : `php artisan migrate --force --seed`.

## 5. Vérification de la configuration

**Ne pas lancer `config:cache`** (voir les pièges ci-dessus).

```bash
php artisan domain:check        # ne doit afficher aucun ✘
php artisan route:cache         # facultatif
php artisan view:cache          # facultatif
```

## 6. Tâche planifiée (cron)

Manager OVH → *Hébergement* → *Tâches automatisées (CRON)* → créer :

| Champ | Valeur |
|---|---|
| Commande | `koinonia/artisan schedule:run` |
| Version PHP | 8.2 (ou la version du site) |
| Fréquence | **toutes les minutes** |

Si la fréquence minimale de l'offre est supérieure à 1 minute, les e-mails en file seront envoyés avec ce retard. Sinon, passer `QUEUE_CONNECTION=sync` (envoi immédiat, plus lent pour l'utilisateur).

Vérification par SSH : `php artisan schedule:list`.

**Ce cron est aussi le battement de cœur de la supervision.** La tâche `monitoring:heartbeat` enregistre chaque minute un battement dans le cache ; sans battement depuis `MONITORING_HEARTBEAT_MAX_AGE` minutes, l'application envoie l'alerte `scheduler_stale` (vérifiée au plus toutes les 5 minutes lors d'une requête web) et `/up` répond 503. Le cron et le site doivent donc partager le même cache : garder `CACHE_DRIVER=file`. Après un `php artisan cache:clear`, le battement est effacé jusqu'à la minute suivante.

## 6 bis. Journaux, alertes et supervision

**Permissions de `storage/logs`.** Les fichiers `app-AAAA-MM-JJ.log`, `security-*`, `mail-*`, `queue-*`, `audit-*` et `laravel-*` sont créés par le premier processus qui écrit ce jour-là : le site (utilisateur principal) ou le cron / une commande SSH (utilisateur secondaire). Un fichier créé par l'un peut être illisible ou non inscriptible pour l'autre : c'est la cause classique d'une **500 sans aucune ligne de log**. Après toute commande `artisan` lancée en SSH :

```bash
chmod -R a+rwX storage bootstrap/cache
```

Ne jamais lancer `config:cache` en SSH : le chemin de `storage/logs` mis en cache serait celui de l'utilisateur SSH (voir les pièges en tête de document).

**Health check `GET /up`** (public, sans session) : JSON `{"status": "ok|degraded", "checks": {...}}` avec `database`, `cache`, `queue` (`pending`, `failed`), `storage` et `scheduler` (`last_beat`). Code **200** si tout est `ok`, **503** sinon. Utilisable par un moniteur de disponibilité ; aucun message d'erreur dans la réponse, le détail est dans `app-*.log` (`health.check.failed`).

```bash
curl -s https://NOUVEAU/up | python3 -m json.tool
```

**Alerte de test** après chaque déploiement ou changement de SMTP / destinataires :

```bash
php artisan monitoring:test-alert            # une alerte [TEST] par règle, ignore le throttle
chmod -R a+rwX storage bootstrap/cache
```

Chaque règle doit afficher `envoyée`. Sinon, lire `storage/logs/mail-*.log` (`alert.mail.failed`) et vérifier `MAIL_*` / `ALERT_MAIL_TO`. Que faire à la réception d'une alerte : runbook dans [logging.md](logging.md#20-runbook-des-alertes).

**Consultation** : *Administration > Journaux* (lecture seule) ; en SSH, `grep '<request_id>' storage/logs/*.log`. Rétention : `LOG_DAILY_DAYS` jours, les fichiers plus anciens sont supprimés automatiquement.

## 7. DNS et SSL

Le point qui a coûté le plus de temps : **vérifier quels serveurs répondent réellement pour le domaine**.

```bash
dig +short NS DOMAINE
dig +short A SOUS-DOMAINE @UN_DES_SERVEURS_DE_NOMS
```

- Si les serveurs de noms sont ceux d'OVH (`dnsNN.ovh.net`), ajouter les enregistrements dans la zone DNS du Manager.
- Sinon (par exemple un autre fournisseur), la zone d'OVH n'est qu'une copie inutilisée : l'enregistrement `A` doit être ajouté **chez le fournisseur qui gère réellement la zone** :

  | Type | Nom | Valeur |
  |---|---|---|
  | `A` | sous-domaine | IP de l'hébergement (onglet *Informations générales*) |
  | `AAAA` | sous-domaine (facultatif) | IPv6 de l'hébergement |

  Ne pas migrer les serveurs de noms vers OVH sans avoir comparé les deux zones enregistrement par enregistrement : le site principal, les e-mails et les autres sous-domaines seraient déplacés.
- Utiliser l'IP de l'hébergement pour un site de test (CDN désactivé). Avec l'option CDN, l'enregistrement pointe vers l'IP du CDN.
- Une fois le nom résolu publiquement, régénérer le certificat SSL dans *Multisite*.

**Tester avant que le DNS soit prêt** : ajouter une ligne dans `/etc/hosts` de votre ordinateur (et la retirer ensuite) :

```bash
echo "IP_HEBERGEMENT SOUS-DOMAINE" | sudo tee -a /etc/hosts
```

ou interroger directement le serveur :

```bash
curl -ik --resolve SOUS-DOMAINE:443:IP_HEBERGEMENT https://SOUS-DOMAINE/login
```

Le serveur d'hébergement lui-même ne résout pas toujours le nom : y utiliser `--resolve` pour tester.

## 8. Vérifications après mise en ligne

- [ ] `https://NOUVEAU/login` s'affiche, certificat valide, pas de contenu mixte.
- [ ] Connexion admin et membre ; déconnexion.
- [ ] Planning véhicules : réservation en temps réel (Pusher).
- [ ] Repas du dimanche, menu de la semaine, images et fichiers téléversés.
- [ ] Export CSV des demandes (`/demandes`, administrateur) : le fichier se télécharge et s'ouvre.
- [ ] QR code du formulaire de demande (`/demande`) : le PNG se télécharge et pointe vers `NOUVEAU`.
- [ ] E-mail réel (approbation de compte) reçu, lien correct.
- [ ] `failed_jobs` et `failed_emails` sans nouvelle entrée après quelques minutes.
- [ ] `storage/logs` sans erreur 500 ni 419 ; fichiers `app-AAAA-MM-JJ.log`… créés et inscriptibles par le site.
- [ ] `https://NOUVEAU/up` renvoie 200 et `"status": "ok"` (`scheduler.last_beat` récent : le cron tourne).
- [ ] `php artisan monitoring:test-alert` : toutes les règles `envoyée`, e-mails reçus par les administrateurs.
- [ ] *Administration > Journaux* affiche les entrées du jour.
- [ ] `APP_DEBUG=false` : une page inexistante n'affiche ni trace ni chemins.
- [ ] `https://NOUVEAU/.env` et `https://NOUVEAU/composer.json` renvoient **404/403**.

## 9. Mises à jour ultérieures

```bash
cd ~/koinonia
git pull --ff-only
composer install --no-dev --optimize-autoloader
php artisan migrate --force
chmod -R a+rwX storage bootstrap/cache
php artisan queue:restart
curl -s -o /dev/null -w '%{http_code}\n' https://NOUVEAU/up   # 200 attendu
```

Ne pas lancer `config:cache`. Si `.env.example` a gagné des variables (`LOG_*`, `ALERT_*`, `MONITORING_*`), les reporter dans `.env` (sinon les valeurs par défaut s'appliquent).

## 10. Dépannage

| Symptôme | Cause probable | Que faire |
|---|---|---|
| « Server Not Found » dans le navigateur | Le nom n'existe pas dans le DNS réellement servi | Section « DNS et SSL » |
| Avertissement de certificat | Certificat non émis pour ce nom | DNS d'abord, puis régénérer le SSL |
| 500 avec page blanche, `laravel.log` vide | Cache de configuration contenant le chemin SSH, ou `storage` non inscriptible | `php artisan config:clear`, `rm -f bootstrap/cache/config.php`, `chmod -R a+rwX storage bootstrap/cache` |
| `getaddrinfo ... Name or service not known` dans le log | Hôte MySQL erroné dans `.env` | Copier l'hôte exact affiché dans l'onglet *Bases de données* |
| `There is no existing directory ... storage/logs` | Chemin SSH mis en cache | Voir la ligne précédente |
| Liens en `http://localhost/...` | `APP_URL` sans `https://` | Corriger `APP_URL` |
| 500 avec référence, ligne dans les logs | Erreur applicative | Coller la référence dans *Administration > Journaux* ; procédure dans [logging.md](logging.md#19-procédure-dinvestigation-à-partir-dun-request_id) |
| `Permission denied` sur `storage/logs/*.log` | Fichier du jour créé par l'utilisateur SSH ou le cron | `chmod -R a+rwX storage bootstrap/cache` |
| Alerte `scheduler_stale`, `/up` en 503 sur `scheduler` | Cron arrêté ou cache vidé | Section 6 ; vérifier la tâche CRON dans le Manager |
| Aucune alerte reçue | SMTP en panne ou pas de destinataire | `php artisan monitoring:test-alert`, puis `mail-*.log` (`alert.mail.failed`, `alert.no_recipients`) |

Pour afficher l'erreur réelle : mettre `APP_DEBUG=true` **quelques instants seulement**, recharger la page, puis remettre `false`.

## 11. Plan de retour

Avant chaque mise à jour : `mysqldump` de la base. En cas de problème : `git checkout <commit précédent>`, restaurer le dump si une migration a modifié le schéma, puis `php artisan queue:restart`.
