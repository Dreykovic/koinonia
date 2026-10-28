# Déploiement sur OVHcloud (Hébergement Web Cloud mutualisé)

Guide pour mettre Koinonia (Laravel 10, PHP ≥ 8.1, MySQL) en production sur un hébergement mutualisé OVHcloud, avec reprise des données existantes. Pour la bascule de domaine et le plan de retour, voir aussi [domain-migration.md](domain-migration.md).

## Particularités de l'application

- **Pas de worker permanent** : la file d'attente est traitée par le scheduler (`queue:work --stop-when-empty` chaque minute, voir `app/Console/Kernel.php`). Un **seul cron** `schedule:run` suffit, pas besoin de Supervisor.
- **Temps réel** : Pusher (service externe). Aucun daemon WebSocket à héberger.
- **Assets Vite** : `public/build` est versionné dans git. Pas besoin de Node sur le serveur.
- **Tâches planifiées** : fermeture des repas (samedi 18h), génération des repas (dimanche 0h), nettoyage des notifications (3h).

## 0. Prérequis

- [ ] Offre Web Cloud avec accès SSH, base MySQL et plusieurs sites (multisite).
- [ ] Domaine rattaché à l'hébergement (zone DNS OVH ou externe) et SSL Let's Encrypt activé.
- [ ] Identifiants Pusher de production.
- [ ] Identifiants SMTP (le serveur mail OVH `ssl0.ovh.net:465` ou un fournisseur tiers) et, si le domaine d'envoi change, SPF/DKIM.

## 1. Configuration de l'hébergement (Manager OVH)

1. **PHP** : Hébergements → *Configuration générale* → version **8.3** (min. 8.1), environnement d'exécution **Production**.
2. **Multisite** : ajouter le domaine avec le **dossier racine `koinonia/public`**. Le point d'entrée est ainsi `public/`, et `.env`, `app/`, `vendor/` ne sont pas exposés sur le web.
   - Si OVH impose `www/` comme racine : garder le `.htaccess` à la racine du projet (il redirige vers `public/`), mais la méthode ci-dessus reste préférable.
3. **SSL** : activer Let's Encrypt (ou le certificat OVH) sur le domaine et `www`, puis forcer le HTTPS.
4. **Base MySQL** : créer la base (*Bases de données* → *Créer*). Noter hôte (`xxxx.mysql.db`), nom, utilisateur, mot de passe.
5. **SSH** : vérifier que l'accès SSH est activé (`ssh USER@sshcluster0xx.hosting.ovh.net`).

## 2. Envoyer le code

Par SSH sur l'hébergement (OVH fournit `git` et `composer`) :

```bash
cd ~
git clone https://github.com/Dreykovic/koinonia.git koinonia   # adapter l'URL du dépôt
cd koinonia
php8.3 -v     # sur OVH, la CLI par défaut peut être plus ancienne : utiliser php8.3
php8.3 ~/composer.phar install --no-dev --optimize-autoloader   # ou: composer install ...
```

Si `composer` n'est pas disponible :

```bash
curl -sS https://getcomposer.org/installer | php8.3
```

## 3. Fichier `.env` de production

Créer `~/koinonia/.env` (jamais commité) :

```dotenv
APP_NAME=Koinonia
APP_ENV=production
APP_KEY=                      # généré à l'étape suivante
APP_DEBUG=false
APP_URL=https://NOUVEAU

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=xxxx.mysql.db
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
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=              # vide sur mutualisé (pas de proxy devant PHP)

MAIL_MAILER=smtp
MAIL_HOST=ssl0.ovh.net
MAIL_PORT=465
MAIL_USERNAME=contact@NOUVEAU
MAIL_PASSWORD=...
MAIL_ENCRYPTION=ssl
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

> `VITE_PUSHER_*` est intégré au bundle au moment du build. Si la clé Pusher de production diffère de celle utilisée à la compilation, relancer `npm run build` en local avec la bonne valeur, committer `public/build` et redéployer.

Puis :

```bash
chmod 600 .env
php8.3 artisan key:generate
chmod -R u+rwX storage bootstrap/cache
```

## 4. Reprise des données

**Sur l'ancien serveur / en local** :

```bash
mysqldump --single-transaction --no-tablespaces -u USER -p BASE > backup-$(date +%F).sql
tar czf storage-$(date +%F).tgz storage/app
```

**Sur l'hébergement OVH** (transférer les fichiers avec `scp`) :

```bash
mysql -h xxxx.mysql.db -u USER -p BASE < backup-AAAA-MM-JJ.sql
tar xzf storage-AAAA-MM-JJ.tgz            # depuis ~/koinonia
php8.3 artisan migrate --force            # applique les migrations manquantes
php8.3 artisan storage:link               # si des fichiers sont servis via public/storage
```

Pour une base vide (première installation) : `php8.3 artisan migrate --force --seed`.

## 5. Optimisation et vérification de la config

```bash
php8.3 artisan config:cache
php8.3 artisan route:cache
php8.3 artisan view:cache
php8.3 artisan domain:check
```

## 6. Tâche planifiée (cron)

Manager OVH → *Hébergement* → *Tâches planifiées (CRON)* → créer :

| Champ | Valeur |
|---|---|
| Commande | `koinonia/artisan schedule:run` |
| Version PHP | 8.3 |
| Fréquence | **toutes les minutes** |

Si la fréquence minimale de l'offre est supérieure à 1 minute, les e-mails en file seront envoyés avec ce retard : le choix de l'offre doit en tenir compte. Sinon, passer `QUEUE_CONNECTION=sync` (envoi immédiat, plus lent pour l'utilisateur).

Vérification par SSH : `php8.3 artisan schedule:list`.

## 7. Bascule DNS

1. Tester avant la bascule via le domaine temporaire OVH ou le fichier `hosts` local.
2. Baisser le TTL du domaine 24-48 h avant.
3. Faire pointer `A`/`AAAA` (et `www`) vers l'IP de l'hébergement (ou utiliser la zone DNS OVH).
4. Rediriger l'ancien domaine en 301 vers le nouveau (voir [domain-migration.md](domain-migration.md), section 2).

## 8. Vérifications après mise en ligne

- [ ] `https://NOUVEAU/login` s'affiche, certificat valide, pas de contenu mixte.
- [ ] Connexion admin et membre ; déconnexion.
- [ ] Planning véhicules : réservation en temps réel (Pusher).
- [ ] Repas du dimanche, menu de la semaine, bulletin PDF.
- [ ] QR code du formulaire de demande pointe vers `NOUVEAU`.
- [ ] E-mail réel (approbation de compte) reçu, lien correct.
- [ ] `failed_jobs` et `failed_emails` sans nouvelle entrée après quelques minutes.
- [ ] `storage/logs` sans erreur 500 ni 419.
- [ ] `https://NOUVEAU/.env` et `https://NOUVEAU/composer.json` renvoient **404/403**.

## 9. Mises à jour ultérieures

```bash
cd ~/koinonia
git pull --ff-only
php8.3 ~/composer.phar install --no-dev --optimize-autoloader
php8.3 artisan migrate --force
php8.3 artisan config:cache && php8.3 artisan route:cache && php8.3 artisan view:cache
php8.3 artisan queue:restart
```

## 10. Plan de retour

Avant chaque mise à jour : `mysqldump` de la base. En cas de problème : `git checkout <commit précédent>`, restaurer le dump si une migration a modifié le schéma, relancer les `config:cache`, `route:cache` et `queue:restart`.
