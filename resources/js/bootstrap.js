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

// Sans clé Pusher (BROADCAST_DRIVER=log en local), pas d'Echo : les vues testent
// typeof Echo et se rabattent sur le polling. Une erreur d'initialisation ne doit
// pas casser le reste de la page.
if (import.meta.env.VITE_PUSHER_APP_KEY) {
    try {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_PUSHER_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
            forceTLS: true,
        });

        watchBroadcastConnection(window.Echo);
    } catch (e) {
        reportClientError({ type: 'error', message: `Echo init failed: ${e.message}`, stack: e.stack });
    }
}
