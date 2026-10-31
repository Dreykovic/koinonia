/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
import { installAxiosInterceptor } from './monitoring/capture';
import { watchBroadcastConnection } from './monitoring/broadcast';
import { reportClientError } from './monitoring/reporter';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Échecs réseau / 5xx remontés à /log/client
installAxiosInterceptor(window.axios);

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// La clé publique Pusher vient de <meta name="pusher-key"> (rendue depuis la config serveur) :
// le bundle commité dans public/build fonctionne quel que soit le .env de la machine de build.
// VITE_PUSHER_APP_KEY reste un repli pour le développement. Sans clé (BROADCAST_DRIVER=log
// en local), pas d'Echo : les vues testent typeof Echo et se rabattent sur le polling.
// Une erreur d'initialisation ne doit pas casser le reste de la page.
const metaContent = (name) => document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') || '';
const pusherKey = metaContent('pusher-key') || import.meta.env.VITE_PUSHER_APP_KEY || '';
const pusherCluster = metaContent('pusher-cluster') || import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1';

if (pusherKey) {
    try {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: pusherKey,
            cluster: pusherCluster,
            forceTLS: true,
        });

        watchBroadcastConnection(window.Echo);
    } catch (e) {
        reportClientError({ type: 'error', message: `Echo init failed: ${e.message}`, stack: e.stack });
    }
}
