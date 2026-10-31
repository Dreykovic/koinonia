<?php

namespace App\Http\Controllers;

use App\Support\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Reçoit les erreurs JavaScript remontées par le navigateur (resources/js/monitoring)
 * et les écrit dans le canal "app". Accessible aux invités comme aux membres,
 * protégé par CSRF et throttle (voir routes/web.php).
 *
 * Tout ce qui vient du client est non fiable : chaque champ est validé, nettoyé des
 * caractères de contrôle, expurgé (tokens, e-mails) puis tronqué avant d'être journalisé.
 * L'utilisateur, la route et le request_id sont ajoutés par AddRequestContext.
 */
class ClientErrorController extends Controller
{
    /** Types acceptés => [clé d'événement, niveau]. */
    private const TYPES = [
        'error' => ['client.js.error', 'error'],
        'rejection' => ['client.js.unhandled_rejection', 'error'],
        'network' => ['client.network.failed', 'warning'],
        'broadcast' => ['client.broadcast.connection_lost', 'warning'],
    ];

    /** Longueurs maximales écrites dans le log (après nettoyage). */
    private const MAX_MESSAGE = 500;

    private const MAX_STACK = 3000;

    private const MAX_FRAMES = 30;

    private const MAX_URL = 500;

    private const MAX_USER_AGENT = 300;

    public function store(Request $request): Response|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:'.implode(',', array_keys(self::TYPES)),
            'message' => 'required|string|max:5000',
            'source' => 'nullable|string|max:2048',
            'line' => 'nullable|integer|min:0|max:10000000',
            'col' => 'nullable|integer|min:0|max:10000000',
            'stack' => 'nullable|string|max:20000',
            'url' => 'nullable|string|max:2048',
            'version' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9._-]+$/'],
            'method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'status' => 'nullable|integer|min:0|max:599',
            'request_url' => 'nullable|string|max:2048',
            'failed_request_id' => ['nullable', 'string', 'regex:/^[A-Za-z0-9-]{8,64}$/'],
            'state' => 'nullable|string|in:unavailable,failed,disconnected,error',
        ]);

        // Pas de log sur une charge invalide : ce serait une porte ouverte au flood.
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Rapport d\'erreur invalide.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        [$event, $level] = self::TYPES[$data['type']];

        $context = array_filter([
            'error' => self::clean($data['message'], self::MAX_MESSAGE),
            'source' => self::clean($data['source'] ?? null, self::MAX_URL),
            'line' => $data['line'] ?? null,
            'col' => $data['col'] ?? null,
            'stack' => self::frames($data['stack'] ?? null),
            'page_url' => self::clean($data['url'] ?? null, self::MAX_URL),
            'http_method' => $data['method'] ?? null,
            'http_status' => $data['status'] ?? null,
            'request_url' => self::clean($data['request_url'] ?? null, self::MAX_URL),
            'failed_request_id' => $data['failed_request_id'] ?? null,
            'state' => $data['state'] ?? null,
            'app_version' => AppVersion::current(),
            // Version de la page côté client : diffère d'app_version si l'onglet est resté ouvert pendant un déploiement.
            'client_version' => $data['version'] ?? null,
            'user_agent' => self::clean((string) $request->userAgent(), self::MAX_USER_AGENT),
        ], fn ($value) => $value !== null && $value !== '');

        Log::channel('app')->log($level, $event, $context);

        return response()->noContent();
    }

    /**
     * Nettoie un texte venu du client : UTF-8 valide, sur une seule ligne (aucun caractère
     * de contrôle : pas de fausse ligne de log injectée), données sensibles expurgées, tronqué.
     */
    public static function clean(?string $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\p{C}+/u', ' ', mb_scrub($value, 'UTF-8'));
        $value = self::redact(trim((string) $value));

        return mb_strlen($value) > $max ? mb_substr($value, 0, $max).'…' : $value;
    }

    /**
     * Stack trace découpée en lignes nettoyées (MAX_FRAMES lignes, MAX_STACK caractères au total) :
     * lisible dans un log ligne à ligne comme en JSON, sans retour à la ligne brut.
     *
     * @return list<string>|null
     */
    public static function frames(?string $stack): ?array
    {
        if ($stack === null || trim($stack) === '') {
            return null;
        }

        $frames = [];
        $length = 0;

        foreach (preg_split('/\R/u', mb_scrub($stack, 'UTF-8')) as $line) {
            $line = self::clean($line, 300);

            if ($line === '' || count($frames) >= self::MAX_FRAMES || $length + mb_strlen($line) > self::MAX_STACK) {
                continue;
            }

            $frames[] = $line;
            $length += mb_strlen($line);
        }

        return $frames ?: null;
    }

    /**
     * Retire tokens et e-mails d'un texte libre (message, URL, stack trace).
     */
    public static function redact(string $value): string
    {
        $patterns = [
            // Lien de réinitialisation de mot de passe : le token est dans le chemin.
            '#(reset-password/)[^/\s?\#:"\'<>]+#i' => '$1[redacted]',
            // Paramètres de requête sensibles (token, signature, password…).
            '/([?&][^=&\s#]*(?:token|signature|password|secret|key|code|email)[^=&\s#]*=)[^&\s#"\'<>]*/i' => '$1[redacted]',
            // Adresses e-mail : "jean@example.com" => "j***@example.com" (cf. LogMask).
            '/\b([A-Za-z0-9])[A-Za-z0-9._%+-]*@([A-Za-z0-9.-]+\.[A-Za-z]{2,})\b/' => '$1***@$2',
        ];

        return (string) preg_replace(array_keys($patterns), array_values($patterns), $value);
    }
}
