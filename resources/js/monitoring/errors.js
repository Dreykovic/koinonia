/**
 * Affichage homogène des erreurs dans le design system.
 *
 * - Pages sous layouts/app : toasts "Toast" (défini dans le layout).
 * - Pages autonomes (planning) : SweetAlert2 (window.Swal), déjà utilisé par ces pages.
 *
 * Les messages sont échappés : Toast et Swal interprètent du HTML.
 */

const GENERIC = 'Une erreur est survenue. Veuillez réessayer.';

const BY_STATUS = {
    0: 'Connexion au serveur impossible. Vérifiez votre connexion internet.',
    419: 'Votre session a expiré. Rechargez la page puis réessayez.',
    429: 'Trop de requêtes. Patientez quelques instants avant de réessayer.',
};

export function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function toast() {
    // "Toast" est une constante globale du layout (script classique), pas une propriété de window.
    // eslint-disable-next-line no-undef
    if (typeof Toast !== 'undefined' && Toast && typeof Toast.show === 'function') return Toast;
    return window.Toast && typeof window.Toast.show === 'function' ? window.Toast : null;
}

/**
 * Message français à afficher pour une erreur AJAX (jqXHR, erreur Axios, Response
 * déjà décodée {status, data}) ou une Error. Les erreurs serveur (5xx) portent la
 * référence request_id renvoyée par le handler, à communiquer au support.
 */
export function errorMessage(error, fallback = GENERIC) {
    if (!error) return fallback;
    if (typeof error === 'string') return error;

    const response = error.response || null; // Axios
    const status = error.status ?? (response ? response.status : undefined);
    const data = error.responseJSON || (response && response.data) || error.data || null;

    let message = data && typeof data.message === 'string' && data.message !== '' ? data.message : null;

    if (status !== undefined && status !== null && BY_STATUS[status] && (!message || status === 0)) {
        message = BY_STATUS[status];
    }
    if (!message && status === undefined && error instanceof Error && error.name === 'TypeError') {
        message = BY_STATUS[0]; // fetch() rejeté : réseau
    }
    message = message || fallback;

    const requestId = (data && data.request_id)
        || (typeof error.getResponseHeader === 'function' ? error.getResponseHeader('X-Request-Id') : null)
        || (response && response.headers ? response.headers['x-request-id'] : null);

    if (status >= 500 && requestId) {
        message += ` (réf. ${requestId})`;
    }

    return message;
}

/** Affiche une erreur bloquante pour l'utilisateur (toast d'erreur, sinon fenêtre Swal). */
export function showError(message, title = 'Erreur') {
    const text = String(message || GENERIC);
    const t = toast();
    if (t) return t.error(escapeHtml(text), 6000);
    if (window.Swal) return window.Swal.fire({ titleText: title, text, icon: 'error' });
    console.error(text);
    return null;
}

/**
 * Notification discrète (info/warning/success). Retourne une fonction pour la fermer.
 */
export function showNotice(message, type = 'info', duration = 5000) {
    const t = toast();
    if (t) {
        const el = t.show(escapeHtml(message), type, duration);
        return () => t.dismiss(el);
    }
    if (window.Swal) {
        window.Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type,
            titleText: String(message),
            showConfirmButton: false,
            timer: duration > 0 ? duration : undefined,
        });
        return () => window.Swal.close();
    }
    return () => {};
}
