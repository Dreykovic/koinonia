/**
 * Surveillance de la connexion temps réel (Echo / Pusher).
 *
 * - unavailable / failed / disconnected : journalisé une fois par état et par page,
 *   puis notice discrète si la coupure dure plus de NOTICE_DELAY_MS.
 * - Reconnexion : Pusher retente seul depuis "unavailable" et se réabonne à tous les
 *   canaux une fois reconnecté (subscribeAll). On relance la connexion nous-mêmes après
 *   un "disconnected" inattendu et au retour du réseau.
 * - Au retour, l'événement window "broadcast:reconnected" permet aux pages de recharger
 *   les données diffusées pendant la coupure.
 */
import { reportClientError } from './reporter';
import { showNotice } from './errors';

const NOTICE_DELAY_MS = 3000;
const RECONNECT_DELAYS_MS = [2000, 5000, 15000, 30000, 60000];

const MESSAGES = {
    lost: 'Connexion temps réel interrompue. Nouvelle tentative en cours…',
    failed: 'Mises à jour en temps réel indisponibles. Actualisez la page pour voir les derniers changements.',
    back: 'Connexion temps réel rétablie.',
};

export function watchBroadcastConnection(echo) {
    const pusher = echo && echo.connector && echo.connector.pusher;
    if (!pusher || !pusher.connection || typeof pusher.connection.bind !== 'function') return;

    const connection = pusher.connection;
    const reported = new Set();
    let lost = false;
    let noticeShown = false;
    let noticeTimer = null;
    let dismissNotice = () => {};
    let reconnectTimer = null;
    let reconnectAttempt = 0;
    let unloading = false;

    window.addEventListener('pagehide', () => { unloading = true; });

    function reportOnce(state, message) {
        if (reported.has(state)) return;
        reported.add(state);
        reportClientError({ type: 'broadcast', message, state });
    }

    function scheduleReconnect() {
        if (reconnectTimer || reconnectAttempt >= RECONNECT_DELAYS_MS.length) return;
        const delay = RECONNECT_DELAYS_MS[reconnectAttempt++];
        reconnectTimer = setTimeout(() => {
            reconnectTimer = null;
            if (!unloading && connection.state === 'disconnected') pusher.connect();
        }, delay);
    }

    function markLost(state) {
        if (lost) return;
        lost = true;
        noticeTimer = setTimeout(() => {
            noticeShown = true;
            dismissNotice = showNotice(state === 'failed' ? MESSAGES.failed : MESSAGES.lost, 'warning', 8000);
        }, NOTICE_DELAY_MS);
    }

    connection.bind('state_change', ({ previous, current }) => {
        if (unloading) return;

        if (current === 'unavailable' || current === 'failed' || current === 'disconnected') {
            reportOnce(current, `Broadcast connection ${current} (from ${previous})`);
            markLost(current);
            if (current === 'disconnected') scheduleReconnect();
            return;
        }

        if (current === 'connected' && lost) {
            lost = false;
            reconnectAttempt = 0;
            clearTimeout(noticeTimer);
            if (noticeShown) {
                dismissNotice();
                showNotice(MESSAGES.back, 'success', 3000);
            }
            noticeShown = false;
            window.dispatchEvent(new CustomEvent('broadcast:reconnected'));
        }
    });

    // Erreurs protocolaires (quota, clé invalide…) : pas de changement d'état systématique.
    connection.bind('error', (event) => {
        if (unloading) return;
        const data = event && event.error && event.error.data;
        const detail = (data && (data.message || data.code)) || (event && event.type) || 'unknown';
        reportOnce('error', `Broadcast connection error: ${detail}`);
    });

    window.addEventListener('online', () => {
        if (['unavailable', 'disconnected'].includes(connection.state)) {
            reconnectAttempt = 0;
            pusher.connect();
        }
    });
}
