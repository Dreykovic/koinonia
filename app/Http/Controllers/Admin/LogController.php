<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LogReader;
use App\Support\LogMask;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Consultation en lecture seule des journaux (storage/logs) par les administrateurs.
 * Chaque consultation est tracée dans le canal audit.
 */
class LogController extends Controller
{
    public const PER_PAGE = 50;

    public const REQUEST_ID_PATTERN = '/^[A-Za-z0-9\-]{8,64}$/';

    public function __construct(private LogReader $reader)
    {
    }

    /**
     * Liste filtrée des entrées, de la plus récente à la plus ancienne.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'from' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'to' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'level' => ['nullable', 'in:'.implode(',', LogReader::LEVELS)],
            'channel' => ['nullable', 'in:'.implode(',', LogReader::CHANNELS)],
            'user' => ['nullable', 'string', 'max:255'],
            'route' => ['nullable', 'string', 'max:150'],
            'q' => ['nullable', 'string', 'max:200'],
            'request_id' => ['nullable', 'regex:'.self::REQUEST_ID_PATTERN],
            'page' => ['nullable', 'integer', 'min:1', 'max:'.LogReader::MAX_PAGE],
        ], [
            'from.date_format' => 'La date de début est invalide.',
            'to.date_format' => 'La date de fin est invalide.',
            'level.in' => 'Niveau inconnu.',
            'channel.in' => 'Canal inconnu.',
            'request_id.regex' => 'Identifiant de requête invalide.',
            'page.max' => 'Affinez les filtres pour consulter des entrées plus anciennes.',
        ]);

        if ($validator->fails()) {
            Log::channel('audit')->warning('admin.logs.invalid_filters', ['fields' => $validator->errors()->keys()]);

            return redirect()->route('admin.logs.index')->withErrors($validator);
        }

        $input = array_filter($validator->validated(), fn ($value) => $value !== null && $value !== '');
        $page = (int) ($input['page'] ?? 1);

        // Sans plage ni request_id : dernières 24 heures par défaut.
        if (! isset($input['from']) && ! isset($input['to']) && ! isset($input['request_id'])) {
            $input['from'] = now()->subDay()->format('Y-m-d\TH:i');
        }

        $filters = [
            'from' => isset($input['from']) ? Carbon::createFromFormat('Y-m-d\TH:i', $input['from'])->startOfMinute() : null,
            'to' => isset($input['to']) ? Carbon::createFromFormat('Y-m-d\TH:i', $input['to'])->endOfMinute() : null,
            'level' => $input['level'] ?? null,
            'channel' => $input['channel'] ?? null,
            'route' => $input['route'] ?? null,
            'q' => $input['q'] ?? null,
            'request_id' => $input['request_id'] ?? null,
        ];

        $matchedUsers = null;
        if (isset($input['user'])) {
            $matchedUsers = $this->resolveUsers($input['user']);
            $filters['user_ids'] = $matchedUsers->keys()->all();
        }

        $result = $this->reader->search($filters, $page, self::PER_PAGE);

        $entries = new LengthAwarePaginator(
            $result['entries'],
            min($result['total'], LogReader::MAX_PAGE * self::PER_PAGE),
            self::PER_PAGE,
            $page,
            ['path' => route('admin.logs.index'), 'query' => $request->except('page')]
        );

        Log::channel('audit')->info('admin.logs.viewed', [
            'filters' => $this->auditFilters($input),
            'page' => $page,
            'results' => $result['total'],
        ]);

        return view('admin.logs.index', [
            'entries' => $entries,
            'total' => $result['total'],
            'input' => $input,
            'matchedUsers' => $matchedUsers,
            'userNames' => $this->userNames($result['entries']),
            'levels' => LogReader::LEVELS,
            'channels' => LogReader::CHANNELS,
        ]);
    }

    /**
     * Détail d'une entrée : contexte complet et stack trace.
     */
    public function show(Request $request)
    {
        $data = Validator::make($request->query(), [
            'channel' => ['required', 'in:'.implode(',', LogReader::CHANNELS)],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'offset' => ['required', 'integer', 'min:0'],
        ])->validate();

        try {
            $entry = $this->reader->find($data['channel'], $data['date'] ?? null, (int) $data['offset']);
        } catch (InvalidArgumentException) {
            // Fichier ou position invalide : tracé ci-dessous (found: false) puis 404.
            $entry = null;
        }

        Log::channel('audit')->info('admin.logs.entry_viewed', [
            'log_channel' => $data['channel'],
            'file_date' => $data['date'] ?? null,
            'offset' => (int) $data['offset'],
            'found' => $entry !== null,
        ]);

        abort_if($entry === null, 404, 'Entrée de journal introuvable.');

        return view('admin.logs.show', [
            'entry' => $entry,
            'userNames' => $this->userNames([$entry]),
        ]);
    }

    /**
     * Chronologie de toutes les lignes d'une requête, tous canaux confondus.
     */
    public function request(Request $request, string $requestId)
    {
        $date = $request->query('date');
        $date = is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? Carbon::createFromFormat('Y-m-d', $date) : null;

        // Une date connue limite la lecture aux fichiers de la veille au lendemain.
        $result = $this->reader->timeline(
            $requestId,
            $date?->copy()->subDay()->startOfDay(),
            $date?->copy()->addDay()->endOfDay(),
        );

        Log::channel('audit')->info('admin.logs.request_viewed', [
            'searched_request_id' => $requestId,
            'results' => count($result['entries']),
        ]);

        return view('admin.logs.request', [
            'requestId' => $requestId,
            'entries' => $result['entries'],
            'truncated' => $result['truncated'],
            'userNames' => $this->userNames($result['entries']),
        ]);
    }

    /**
     * Utilisateurs correspondant au filtre : identifiant, e-mail exact ou nom partiel.
     *
     * @return \Illuminate\Support\Collection<int, string> noms indexés par id
     */
    private function resolveUsers(string $value)
    {
        $value = trim($value);

        if (ctype_digit($value)) {
            return collect([(int) $value => User::whereKey((int) $value)->value('name') ?? '#'.$value]);
        }

        $like = '%'.addcslashes($value, '%_\\').'%';

        return User::query()
            ->where('email', $value)
            ->orWhere('name', 'like', $like)
            ->orderBy('name')
            ->limit(50)
            ->pluck('name', 'id');
    }

    /**
     * Noms des utilisateurs cités par les entrées affichées.
     */
    private function userNames(array $entries): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            fn ($entry) => ctype_digit((string) $entry['user_id']) ? (int) $entry['user_id'] : null,
            $entries
        ))));

        return $ids === [] ? [] : User::whereIn('id', $ids)->pluck('name', 'id')->all();
    }

    /**
     * Filtres tracés dans audit : e-mail masqué, texte libre tronqué.
     */
    private function auditFilters(array $input): array
    {
        unset($input['page']);

        if (isset($input['user']) && str_contains($input['user'], '@')) {
            $input['user'] = LogMask::email($input['user']);
        }
        if (isset($input['q'])) {
            $input['q'] = Str::limit($input['q'], 100);
        }

        return $input;
    }
}
