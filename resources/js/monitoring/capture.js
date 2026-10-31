/**
 * Capture des erreurs du navigateur : exceptions non gérées, promesses rejetées sans
 * catch, et échecs AJAX (fetch, Axios, jQuery). Tout passe par reportClientError
 * (dédoublonnage + quota).
 *
 * Les réponses 4xx (validation, 419, 429…) sont attendues et ne sont pas remontées :
 * seuls les échecs réseau (status 0) et les erreurs serveur (5xx) le sont, le 5xx
 * étant déjà journalisé côté serveur (corrélé via failed_request_id).
 */
import { isReportUrl, reportClientError } from './reporter';

const REPORTED = '__koinoniaReported';

// Bruit sans valeur de diagnostic : extensions, scripts tiers opaques, boucle ResizeObserver.
const IGNORED_MESSAGES = [/^Script error\.?$/i, /ResizeObserver loop/i];
const IGNORED_SOURCES = /^(chrome|moz|safari|safari-web)-extension:/i;

function markReported(error) {
    try {
        if (error && typeof error === 'object') error[REPORTED] = true;
    } catch (e) { /* objet gelé : tant pis */ }
}

function isAbort(error) {
    return !!error && (error.name === 'AbortError' || error.name === 'CanceledError' || error.__CANCEL__ === true);
}

function describe(reason) {
    if (reason instanceof Error) {
        return { message: `${reason.name}: ${reason.message}`, stack: reason.stack };
    }
    if (typeof reason === 'string') return { message: reason };
    try {
        return { message: JSON.stringify(reason) || String(reason) };
    } catch (e) {
        return { message: String(reason) };
    }
}

/**
 * Remonte un échec AJAX si c'est une erreur réseau ou serveur.
 */
export function reportNetworkFailure({ method, url, status, requestId }) {
    if (!url || isReportUrl(url)) return;
    const code = Number(status) || 0;
    if (code > 0 && code < 500) return;
    // Hors ligne : échec attendu, et le rapport ne pourrait pas partir.
    if (code === 0 && navigator.onLine === false) return;

    reportClientError({
        type: 'network',
        message: code ? `HTTP ${code}` : 'Network error',
        method: method || 'GET',
        request_url: url,
        status: code,
        failed_request_id: requestId || undefined,
    });
}

function installGlobalHandlers() {
    window.addEventListener('error', (event) => {
        // Les erreurs de chargement de ressources (img, script) ne sont pas des ErrorEvent.
        if (!(event instanceof ErrorEvent)) return;
        const message = event.message || (event.error && event.error.message) || '';
        if (IGNORED_MESSAGES.some((re) => re.test(message))) return;
        if (event.filename && IGNORED_SOURCES.test(event.filename)) return;

        reportClientError({
            type: 'error',
            message,
            source: event.filename,
            line: event.lineno,
            col: event.colno,
            stack: event.error && event.error.stack,
        });
    });

    window.addEventListener('unhandledrejection', (event) => {
        const reason = event.reason;
        // Échec AJAX déjà remonté (ou 4xx volontairement ignoré) par la couche réseau.
        if (reason && reason[REPORTED]) return;
        if (isAbort(reason)) return;

        reportClientError({ type: 'rejection', ...describe(reason) });
    });
}

function wrapFetch() {
    if (typeof window.fetch !== 'function') return;
    const originalFetch = window.fetch;

    window.fetch = function (input, init) {
        const url = typeof input === 'string' ? input : (input && input.url) || String(input);
        const method = (init && init.method) || (input && input.method) || 'GET';

        return originalFetch.apply(this, arguments).then(
            (response) => {
                if (response.status >= 500) {
                    reportNetworkFailure({ method, url, status: response.status, requestId: response.headers.get('X-Request-Id') });
                }
                return response;
            },
            (error) => {
                if (!isAbort(error)) {
                    reportNetworkFailure({ method, url, status: 0 });
                    markReported(error);
                }
                throw error;
            },
        );
    };
}

/**
 * Intercepteur Axios (appelé par bootstrap.js sur l'instance exposée en window.axios).
 */
export function installAxiosInterceptor(axios) {
    if (!axios || !axios.interceptors) return;

    axios.interceptors.response.use(
        (response) => response,
        (error) => {
            if (!isAbort(error) && !(axios.isCancel && axios.isCancel(error))) {
                const config = error.config || {};
                const response = error.response;
                reportNetworkFailure({
                    method: config.method,
                    url: config.url,
                    status: response ? response.status : 0,
                    requestId: response && response.headers ? response.headers['x-request-id'] : null,
                });
            }
            markReported(error);
            return Promise.reject(error);
        },
    );
}

function hookJQuery() {
    const $ = window.jQuery;
    if (!$ || typeof $.fn !== 'object' || $.__koinoniaAjaxHooked) return !!$;
    $.__koinoniaAjaxHooked = true;

    $(document).ajaxError((event, xhr, settings) => {
        if (!xhr || xhr.statusText === 'abort') return;
        reportNetworkFailure({
            method: settings && settings.type,
            url: settings && settings.url,
            status: xhr.status,
            requestId: typeof xhr.getResponseHeader === 'function' ? xhr.getResponseHeader('X-Request-Id') : null,
        });
    });

    return true;
}

export function installErrorCapture() {
    if (window.__koinoniaErrorCapture) return;
    window.__koinoniaErrorCapture = true;

    installGlobalHandlers();
    wrapFetch();

    // jQuery est chargé par plugins.bundle.js : en général avant ce module (différé),
    // sinon on réessaie une fois le DOM prêt.
    if (!hookJQuery()) {
        document.addEventListener('DOMContentLoaded', hookJQuery, { once: true });
    }
}
