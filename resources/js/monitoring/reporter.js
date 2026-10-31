/**
 * Envoi des erreurs client vers POST /log/client (ClientErrorController), journalisées
 * localement dans le canal "app". Aucun service externe.
 *
 * Garde-fous contre le flood : une même erreur n'est envoyée qu'une fois par page,
 * au plus MAX_PER_MINUTE envois par minute et MAX_PER_PAGE par chargement de page
 * (le serveur applique en plus throttle:20,1 par IP).
 */

const DEFAULT_ENDPOINT = '/log/client';
const MAX_PER_MINUTE = 5;
const MAX_PER_PAGE = 20;

const LIMITS = { message: 1000, stack: 4000, source: 1000, request_url: 1000, url: 1000 };
const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
const REQUEST_ID = /^[A-Za-z0-9-]{8,64}$/;

// Référence native capturée avant que capture.js n'enveloppe window.fetch :
// l'envoi d'un rapport ne doit jamais se signaler lui-même.
const nativeFetch = typeof window.fetch === 'function' ? window.fetch.bind(window) : null;

const seen = new Set();
const sentAt = [];
let sentTotal = 0;

function meta(name) {
    const el = document.querySelector(`meta[name="${name}"]`);
    return el ? el.getAttribute('content') : null;
}

export function endpoint() {
    return meta('client-log-url') || DEFAULT_ENDPOINT;
}

/** Vrai si l'URL vise l'endpoint de rapport (évite les boucles). */
export function isReportUrl(url) {
    try {
        return new URL(String(url), window.location.href).pathname === new URL(endpoint(), window.location.href).pathname;
    } catch (e) {
        return false;
    }
}

/** URL courte et sans fragment : chemin relatif si même origine. */
export function shortUrl(url) {
    try {
        const parsed = new URL(String(url), window.location.href);
        return parsed.origin === window.location.origin
            ? parsed.pathname + parsed.search
            : parsed.origin + parsed.pathname + parsed.search;
    } catch (e) {
        return String(url);
    }
}

function truncate(value, max) {
    if (value === undefined || value === null) return undefined;
    const text = String(value);
    return text.length > max ? text.slice(0, max) : text;
}

function toInt(value) {
    const n = Number(value);
    return Number.isInteger(n) && n >= 0 ? n : undefined;
}

/**
 * Envoie un rapport. Retourne true s'il est parti, false s'il a été filtré
 * (doublon, quota atteint) ou si l'envoi est impossible. Ne lève jamais.
 *
 * @param {{type: 'error'|'rejection'|'network'|'broadcast', message: string, source?: string,
 *          line?: number, col?: number, stack?: string, method?: string, status?: number,
 *          request_url?: string, failed_request_id?: string, state?: string}} report
 */
export function reportClientError(report) {
    try {
        if (!nativeFetch || !report || !report.type) return false;

        const payload = {
            type: report.type,
            message: truncate(report.message || 'Unknown error', LIMITS.message),
            source: truncate(report.source, LIMITS.source),
            line: toInt(report.line),
            col: toInt(report.col),
            stack: truncate(report.stack, LIMITS.stack),
            method: METHODS.includes(String(report.method).toUpperCase()) ? String(report.method).toUpperCase() : undefined,
            status: toInt(report.status),
            request_url: report.request_url ? truncate(shortUrl(report.request_url), LIMITS.request_url) : undefined,
            // Mêmes règles que la validation serveur : un champ invalide ferait rejeter tout le rapport.
            failed_request_id: REQUEST_ID.test(report.failed_request_id || '') ? report.failed_request_id : undefined,
            state: report.state || undefined,
        };

        const signature = [payload.type, payload.message, payload.source, payload.line, payload.col,
            payload.method, payload.request_url, payload.status, payload.state].join('|');
        if (seen.has(signature)) return false;

        const now = Date.now();
        while (sentAt.length && now - sentAt[0] > 60000) sentAt.shift();
        if (sentAt.length >= MAX_PER_MINUTE || sentTotal >= MAX_PER_PAGE) return false;

        seen.add(signature);
        sentAt.push(now);
        sentTotal++;

        payload.url = truncate(window.location.origin + window.location.pathname + window.location.search, LIMITS.url);
        payload.version = meta('app-version') || undefined;

        nativeFetch(endpoint(), {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': meta('csrf-token') || '',
            },
            body: JSON.stringify(payload),
        }).catch(() => {});

        return true;
    } catch (e) {
        return false;
    }
}
