# Migration vers un nouveau nom de domaine

Guide pour migrer Koinonia d'un domaine `ANCIEN` vers `NOUVEAU` **sans perte de données**.
Remplacer `ANCIEN` / `NOUVEAU` par les vrais noms de domaine.

## Ce que le code fait (et ne fait pas)

- Tous les liens (e-mails, QR code du formulaire de demande, notifications) sont générés avec `route()` : ils suivent `APP_URL`. **Aucune URL en dur** dans `app/`, `routes/` ni `resources/views`.
- Cookies de session non liés à un domaine (`SESSION_DOMAIN` vide) : ils suivent l'hôte. Les utilisateurs devront se **reconnecter** une fois sur le nouveau domaine.
- Les assets Vite (`public/build`) sont relatifs : pas de rebuild nécessaire, sauf si `PUSHER_*` / `VITE_*` changent.
- Derrière un proxy/CDN, définir `TRUSTED_PROXIES` (voir plus bas), sinon le https n'est pas détecté.
- `php artisan domain:check` vérifie la cohérence de la configuration.

## 0. Prérequis (à demander / confirmer)

- [ ] Nom du nouveau domaine, et qui gère le DNS.
- [ ] Hébergement : même serveur ou nouveau serveur ? (Apache/Nginx, accès SSH)
- [ ] Certificat SSL pour `NOUVEAU` (Let's Encrypt ou fourni par l'hébergeur).
- [ ] Expéditeur des e-mails : `MAIL_FROM_ADDRESS` change-t-il ? (SPF/DKIM du nouveau domaine si oui)
- [ ] Compte Pusher : restrictions d'origine éventuelles à mettre à jour.

## 1. Avant la bascule (J-2)

1. Baisser le TTL DNS de `ANCIEN` à 300 s (24-48 h avant).
2. **Sauvegarder** :
   ```bash
   mysqldump --single-transaction -u USER -p BASE > backup-$(date +%F).sql
   tar czf storage-$(date +%F).tgz storage/app
   cp .env .env.backup-$(date +%F)
   ```
3. Si nouveau serveur : copier le code, `composer install --no-dev`, restaurer le dump et `storage/app`, tester avec le fichier `hosts` local avant de toucher au DNS.
4. Créer le vhost `NOUVEAU` et installer le certificat SSL.

## 2. Bascule

1. Mettre à jour `.env` (production) :
   ```dotenv
   APP_URL=https://NOUVEAU
   SESSION_SECURE_COOKIE=true
   TRUSTED_PROXIES=*            # seulement si proxy/CDN devant l'application
   # SESSION_DOMAIN=            # laisser vide
   # SANCTUM_STATEFUL_DOMAINS=NOUVEAU   # seulement si l'API est utilisée en SPA
   # MAIL_FROM_ADDRESS=...      # si l'expéditeur change
   ```
2. Recharger la configuration :
   ```bash
   php artisan config:clear && php artisan config:cache
   php artisan route:cache && php artisan view:clear
   php artisan queue:restart
   php artisan domain:check
   ```
   `queue:restart` est important : le worker garde l'ancienne configuration en mémoire et enverrait des e-mails avec l'ancien lien.
3. Pointer le DNS de `NOUVEAU` vers le serveur.
4. **Garder `ANCIEN` actif** et le rediriger en 301 vers `NOUVEAU` (les QR codes déjà imprimés, les favoris et les anciens e-mails continuent de fonctionner) :
   - Apache (vhost de `ANCIEN`) :
     ```apache
     RewriteEngine On
     RewriteCond %{HTTP_HOST} ^(www\.)?ANCIEN$ [NC]
     RewriteRule ^ https://NOUVEAU%{REQUEST_URI} [R=301,L]
     ```
   - Nginx :
     ```nginx
     server { server_name ANCIEN www.ANCIEN; return 301 https://NOUVEAU$request_uri; }
     ```
   Ne pas laisser expirer le domaine `ANCIEN` : garder le renouvellement tant que des QR codes imprimés circulent.

## 3. Vérifications après bascule

- [ ] `https://NOUVEAU/login` s'affiche, certificat valide, pas de contenu mixte.
- [ ] Connexion admin et membre ; déconnexion.
- [ ] `ANCIEN/...` redirige en 301 vers `NOUVEAU/...` (chemin conservé).
- [ ] Planning véhicules : réservation en temps réel (WebSockets Pusher).
- [ ] Repas du dimanche, menu de la semaine, bulletin (PDF).
- [ ] Formulaire de demande communautaire : télécharger le QR code, le scanner, il doit pointer vers `NOUVEAU`.
- [ ] Envoyer un e-mail réel (ex. approbation de compte) : le lien du bouton pointe vers `NOUVEAU`.
- [ ] Tâches planifiées : `crontab -l` contient bien `php artisan schedule:run` ; `php artisan schedule:list`.
- [ ] File d'attente : `failed_emails` et `failed_jobs` sans nouvelle entrée.
- [ ] Logs `storage/logs` sans erreur 419 (CSRF/session) ni 500.

## 4. Plan de retour

Tant que `ANCIEN` n'est pas supprimé : remettre `.env.backup-*`, `php artisan config:cache`, `php artisan queue:restart`, et repointer le DNS. Aucune migration de base de données n'est liée au changement de domaine : il n'y a rien à annuler côté données.
