<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use SplMinHeap;
use Throwable;

/**
 * Lecture en flux des fichiers de storage/logs pour la page admin « Journaux ».
 *
 * - Seuls les fichiers des canaux connus sont lus (CHANNELS + regex FILE_PATTERN) : le nom de
 *   fichier est toujours reconstruit à partir d'un canal et d'une date validés, jamais reçu tel quel.
 * - Les fichiers sont lus ligne par ligne (fgets) ; une entrée multi-lignes (stack trace) est
 *   regroupée jusqu'au début de l'entrée suivante.
 * - Formats supportés : LineFormatter de Laravel ("[date] env.LEVEL: message {context} {extra}")
 *   et JsonFormatter (un objet JSON par ligne, LOG_FORMATTER).
 * - Pagination du plus récent au plus ancien sans tout charger : seuls les fichiers de la plage
 *   de dates sont lus, et un tas (SplMinHeap) borné à page × perPage garde les positions
 *   (fichier, offset) des entrées les plus récentes. Les entrées de la page sont ensuite relues
 *   par fseek. La mémoire reste proportionnelle à la page demandée, pas à la taille des logs.
 */
class LogReader
{
    public const CHANNELS = ['app', 'security', 'mail', 'queue', 'audit', 'laravel'];

    /** Niveaux Monolog, du moins au plus grave. */
    public const LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    /** Seuls noms de fichiers lisibles : <canal>.log ou <canal>-AAAA-MM-JJ.log. */
    public const FILE_PATTERN = '/^(app|security|mail|queue|audit|laravel)(?:-(\d{4}-\d{2}-\d{2}))?\.log$/';

    public const MAX_PAGE = 200;

    /** Taille maximale conservée pour une entrée (au-delà, elle est tronquée). */
    private const MAX_ENTRY_BYTES = 1048576;

    /** Nombre maximal de lignes affichées pour un request_id. */
    public const MAX_TIMELINE = 1000;

    private const LINE_HEADER = '/^\[(\d{4}-\d{2}-\d{2}[T ][0-9:.]+(?:[+-]\d{2}:?\d{2}|Z)?)\] ([^\s.]+(?:\.[^\s.]+)*)\.([A-Za-z]+): /';

    private string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = rtrim($directory ?? storage_path('logs'), DIRECTORY_SEPARATOR);
    }

    public function directory(): string
    {
        return $this->directory;
    }

    /**
     * Page d'entrées correspondant aux filtres, de la plus récente à la plus ancienne.
     *
     * Filtres : from, to (Carbon), level (minimum), channel, user_ids (int[]), route, q, request_id.
     *
     * @return array{entries: array<int, array>, total: int}
     */
    public function search(array $filters, int $page = 1, int $perPage = 50): array
    {
        $page = max(1, min($page, self::MAX_PAGE));
        $keep = $page * $perPage;
        $files = $this->candidateFiles($filters);

        // Tas des $keep entrées les plus récentes : [timestamp, seq, index fichier, offset].
        $heap = new SplMinHeap();
        $total = 0;
        $seq = 0;

        foreach ($files as $index => $file) {
            foreach ($this->entries($file) as $entry) {
                $seq++;

                if (! $this->matches($entry, $filters)) {
                    continue;
                }

                $total++;
                $heap->insert([$entry['timestamp'], $seq, $index, $entry['offset']]);

                if ($heap->count() > $keep) {
                    $heap->extract();
                }
            }
        }

        $positions = [];
        foreach ($heap as $item) {
            $positions[] = $item;
        }
        // Le tas sort du plus ancien au plus récent : on inverse puis on découpe la page.
        $positions = array_slice(array_reverse($positions), ($page - 1) * $perPage, $perPage);

        $entries = [];
        foreach ($positions as [, , $index, $offset]) {
            $file = $files[$index];
            $entry = $this->readAt($file['path'], $offset);
            if ($entry !== null) {
                $entries[] = $this->decorate($entry, $file);
            }
        }

        return ['entries' => $entries, 'total' => $total];
    }

    /**
     * Toutes les lignes d'une requête (request_id exact ou préfixe d'au moins 8 caractères),
     * tous canaux confondus, dans l'ordre chronologique.
     *
     * @return array{entries: array<int, array>, truncated: bool}
     */
    public function timeline(string $requestId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $filters = ['request_id' => $requestId, 'from' => $from, 'to' => $to];
        $matches = [];
        $seq = 0;
        $truncated = false;

        foreach ($this->candidateFiles($filters) as $file) {
            foreach ($this->entries($file) as $entry) {
                $seq++;
                if (! $this->matches($entry, $filters)) {
                    continue;
                }
                if (count($matches) >= self::MAX_TIMELINE) {
                    $truncated = true;
                    break 2;
                }
                $matches[] = [$entry['timestamp'], $seq, $this->decorate($entry, $file)];
            }
        }

        usort($matches, fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return ['entries' => array_column($matches, 2), 'truncated' => $truncated];
    }

    /**
     * Une entrée précise, identifiée par son canal, la date du fichier et son offset.
     */
    public function find(string $channel, ?string $date, int $offset): ?array
    {
        $file = $this->file($channel, $date);

        if ($file === null || $offset < 0 || $offset >= filesize($file['path'])) {
            return null;
        }

        $entry = $this->readAt($file['path'], $offset);

        return $entry === null ? null : $this->decorate($entry, $file);
    }

    /**
     * Fichier autorisé correspondant à un canal et une date (null = fichier sans date).
     * Lève une exception pour toute valeur hors liste blanche.
     *
     * @return array{path: string, channel: string, date: ?string}|null
     */
    public function file(string $channel, ?string $date): ?array
    {
        if (! in_array($channel, self::CHANNELS, true)) {
            throw new InvalidArgumentException('Canal de log inconnu.');
        }
        if ($date !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException('Date de fichier invalide.');
        }

        $name = $channel.($date !== null ? '-'.$date : '').'.log';

        return $this->allowed($name);
    }

    /**
     * Fichiers lisibles du répertoire, triés du plus récent au plus ancien.
     *
     * @return array<int, array{path: string, channel: string, date: ?string, mtime: int}>
     */
    public function files(): array
    {
        $names = is_dir($this->directory) ? (scandir($this->directory) ?: []) : [];
        $files = [];

        foreach ($names as $name) {
            if ($file = $this->allowed($name)) {
                $files[] = $file + ['mtime' => (int) filemtime($file['path'])];
            }
        }

        usort($files, fn ($a, $b) => [$b['date'] ?? date('Y-m-d', $b['mtime']), $a['channel']]
            <=> [$a['date'] ?? date('Y-m-d', $a['mtime']), $b['channel']]);

        return $files;
    }

    /**
     * Fichiers à lire pour les filtres : canal et plage de dates, d'après le nom du fichier
     * (ou sa date de modification pour laravel.log, qui n'est pas daté).
     */
    private function candidateFiles(array $filters): array
    {
        $from = ($filters['from'] ?? null)?->format('Y-m-d');
        $to = ($filters['to'] ?? null)?->format('Y-m-d');
        $channel = $filters['channel'] ?? null;

        return array_values(array_filter($this->files(), function ($file) use ($from, $to, $channel) {
            if ($channel && $file['channel'] !== $channel) {
                return false;
            }
            if ($file['date'] === null) {
                // Fichier unique : contient tout jusqu'à sa dernière écriture.
                return $from === null || date('Y-m-d', $file['mtime']) >= $from;
            }

            return ($from === null || $file['date'] >= $from) && ($to === null || $file['date'] <= $to);
        }));
    }

    /**
     * Vérifie un nom de fichier contre la liste blanche et le confinement au répertoire.
     */
    private function allowed(string $name): ?array
    {
        if (! preg_match(self::FILE_PATTERN, $name, $m)) {
            return null;
        }

        $path = $this->directory.DIRECTORY_SEPARATOR.$name;
        $real = realpath($path);
        $root = realpath($this->directory);

        if ($real === false || $root === false || dirname($real) !== $root || ! is_file($real) || ! is_readable($real)) {
            return null;
        }

        return ['path' => $real, 'channel' => $m[1], 'date' => ($m[2] ?? '') !== '' ? $m[2] : null];
    }

    /**
     * Parcourt un fichier en flux et renvoie les entrées une par une (multi-lignes regroupées).
     */
    private function entries(array $file): \Generator
    {
        $handle = @fopen($file['path'], 'rb');
        if ($handle === false) {
            return;
        }

        try {
            $buffer = null;
            $start = 0;

            while (true) {
                $position = ftell($handle);
                $line = fgets($handle);

                if ($line === false || $this->isEntryStart($line)) {
                    if ($buffer !== null && ($entry = $this->parse($buffer, $start)) !== null) {
                        yield $entry;
                    }
                    if ($line === false) {
                        break;
                    }
                    $buffer = $line;
                    $start = $position;

                    continue;
                }

                // Ligne de continuation (stack trace) ; ignorée avant la première entrée.
                if ($buffer !== null && strlen($buffer) < self::MAX_ENTRY_BYTES) {
                    $buffer .= $line;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Relit l'entrée qui commence exactement à $offset (sinon null).
     */
    private function readAt(string $path, int $offset): ?array
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }

        try {
            // L'offset doit être un début de ligne.
            if ($offset > 0) {
                fseek($handle, $offset - 1);
                if (fgetc($handle) !== "\n") {
                    return null;
                }
            }
            fseek($handle, $offset);

            $buffer = fgets($handle);
            if ($buffer === false || ! $this->isEntryStart($buffer)) {
                return null;
            }

            while (($line = fgets($handle)) !== false && ! $this->isEntryStart($line)) {
                if (strlen($buffer) < self::MAX_ENTRY_BYTES) {
                    $buffer .= $line;
                }
            }

            return $this->parse($buffer, $offset);
        } finally {
            fclose($handle);
        }
    }

    private function isEntryStart(string $line): bool
    {
        return str_starts_with($line, '{"message":') || preg_match(self::LINE_HEADER, $line) === 1;
    }

    /**
     * Analyse une entrée brute (format ligne ou JSON).
     */
    public function parse(string $raw, int $offset = 0): ?array
    {
        $raw = rtrim($raw, "\r\n");

        if (str_starts_with($raw, '{"message":')) {
            return $this->parseJson($raw, $offset);
        }

        if (! preg_match(self::LINE_HEADER, $raw, $m)) {
            return null;
        }

        $datetime = $this->date($m[1]);
        if ($datetime === null) {
            return null;
        }

        [$message, $context, $extra] = $this->splitLine(substr($raw, strlen($m[0])));

        return $this->entry($raw, $offset, 'line', $datetime, strtolower($m[3]), $message, $context, $extra);
    }

    private function parseJson(string $raw, int $offset): ?array
    {
        $data = json_decode($raw, true);

        if (! is_array($data) || ! isset($data['datetime'])) {
            return null;
        }

        $datetime = $this->date((string) $data['datetime']);
        if ($datetime === null) {
            return null;
        }

        return $this->entry(
            $raw,
            $offset,
            'json',
            $datetime,
            strtolower((string) ($data['level_name'] ?? 'debug')),
            (string) ($data['message'] ?? ''),
            is_array($data['context'] ?? null) ? $data['context'] : [],
            is_array($data['extra'] ?? null) ? $data['extra'] : [],
        );
    }

    /**
     * Sépare "message {context} {extra}". Le contexte peut contenir des retours à la ligne
     * (stack trace de LineFormatter) : ils sont échappés avant json_decode.
     *
     * @return array{0: string, 1: array, 2: array}
     */
    private function splitLine(string $rest): array
    {
        $rest = rtrim($rest);

        // Débuts possibles du contexte : " {" ou " [" (au plus 20 essais, de gauche à droite).
        // Laravel omet contexte et extra vides : on essaie "{context}" seul, puis "{context} {extra}".
        foreach (array_slice($this->positions($rest, 0), 0, 20) as $i) {
            $tail = str_replace(["\r", "\n", "\t"], ['\r', '\n', '\t'], substr($rest, $i + 1));

            $context = json_decode($tail, true);
            if (is_array($context)) {
                return [substr($rest, 0, $i), $context, []];
            }

            // Fin possible du contexte : dernier " {" ou " [" d'abord (extra le plus court).
            foreach (array_slice(array_reverse($this->positions($tail, 1)), 0, 20) as $j) {
                $context = json_decode(substr($tail, 0, $j), true);
                $extra = json_decode(substr($tail, $j + 1), true);

                if (is_array($context) && is_array($extra)) {
                    return [substr($rest, 0, $i), $context, $extra];
                }
            }
        }

        return [$rest, [], []];
    }

    /**
     * Positions des séquences " {" et " [" dans une chaîne, à partir de $from.
     *
     * @return int[]
     */
    private function positions(string $text, int $from): array
    {
        preg_match_all('/ (?=[\[{])/', $text, $m, PREG_OFFSET_CAPTURE, $from);

        return array_column($m[0], 1);
    }

    private function entry(string $raw, int $offset, string $format, Carbon $datetime, string $level, string $message, array $context, array $extra): array
    {
        if (! in_array($level, self::LEVELS, true)) {
            $level = 'debug';
        }

        return [
            'offset' => $offset,
            'format' => $format,
            'datetime' => $datetime,
            'timestamp' => (float) $datetime->format('U.u'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'extra' => $extra,
            'raw' => $raw,
            'request_id' => is_string($context['request_id'] ?? null) ? $context['request_id'] : null,
            'user_id' => is_scalar($context['user_id'] ?? null) ? (string) $context['user_id'] : null,
            'route' => is_string($context['route'] ?? null) ? $context['route'] : null,
        ];
    }

    /**
     * Ajoute les informations du fichier et sépare la stack trace du contexte.
     */
    private function decorate(array $entry, array $file): array
    {
        $entry['channel'] = $file['channel'];
        $entry['file_date'] = $file['date'];
        [$entry['exception'], $entry['trace']] = $this->exception($entry['context']['exception'] ?? null);

        return $entry;
    }

    /**
     * Résumé de l'exception et stack trace (chaîne LineFormatter ou tableau JsonFormatter).
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function exception(mixed $exception): array
    {
        if (is_string($exception)) {
            $parts = preg_split('/\R\[stacktrace\]\R/', $exception, 2);

            return [trim($parts[0]), isset($parts[1]) ? rtrim($parts[1]) : null];
        }

        if (is_array($exception)) {
            $summary = trim(($exception['class'] ?? 'Exception').': '.($exception['message'] ?? '')
                .(isset($exception['file']) ? ' at '.$exception['file'] : ''));
            $trace = $exception['trace'] ?? null;

            if (is_array($trace)) {
                $trace = implode("\n", array_map(
                    fn ($frame, $i) => '#'.$i.' '.(is_string($frame) ? $frame : json_encode($frame)),
                    $trace,
                    array_keys($trace)
                ));
            }

            return [$summary, is_string($trace) && $trace !== '' ? $trace : null];
        }

        return [null, null];
    }

    private function matches(array $entry, array $filters): bool
    {
        if (($from = $filters['from'] ?? null) && $entry['datetime']->lt($from)) {
            return false;
        }
        if (($to = $filters['to'] ?? null) && $entry['datetime']->gt($to)) {
            return false;
        }

        if (($level = $filters['level'] ?? null)
            && array_search($entry['level'], self::LEVELS, true) < array_search($level, self::LEVELS, true)) {
            return false;
        }

        if (array_key_exists('user_ids', $filters) && $filters['user_ids'] !== null
            && ! in_array($entry['user_id'], array_map('strval', $filters['user_ids']), true)) {
            return false;
        }

        if (($route = $filters['route'] ?? null)
            && ($entry['route'] === null || stripos($entry['route'], $route) === false)) {
            return false;
        }

        if (($requestId = $filters['request_id'] ?? null) && ! $this->sameRequest($entry['context'], $requestId)) {
            return false;
        }

        if (($q = $filters['q'] ?? null) && mb_stripos($entry['raw'], $q) === false) {
            return false;
        }

        return true;
    }

    /**
     * Même requête : request_id, ou requête d'origine (job en file) / requête en échec
     * (erreur front-end). Un préfixe d'au moins 8 caractères suffit (« ref. abcd1234 »).
     */
    private function sameRequest(array $context, string $requestId): bool
    {
        foreach (['request_id', 'origin_request_id', 'failed_request_id'] as $key) {
            $value = $context[$key] ?? null;

            if (is_string($value) && ($value === $requestId || (strlen($requestId) >= 8 && str_starts_with($value, $requestId)))) {
                return true;
            }
        }

        return false;
    }

    private function date(string $value): ?Carbon
    {
        try {
            return Carbon::parse($value)->setTimezone(config('app.timezone'));
        } catch (Throwable) {
            return null;
        }
    }
}
