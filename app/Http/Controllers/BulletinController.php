<?php

namespace App\Http\Controllers;

use App\Enums\PlatformRole;
use App\Http\Requests\StoreWeeklyActivityRequest;
use App\Http\Requests\UpdateWeeklyActivityRequest;
use App\Models\BulletinEdition;
use App\Models\SpiritualReflection;
use App\Models\User;
use App\Models\WeeklyActivity;
use App\Models\WeeklyService;
use App\Services\BirthdayService;
use App\Traits\LogsErrors;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

/**
 * Bulletin hebdomadaire.
 *
 * Gestion d'erreur : les écritures (édition, publication, contenu) sont encadrées par
 * attempt() qui journalise "bulletin.<action>.failed" et renvoie l'utilisateur sur le
 * formulaire avec un message ; les pages de lecture se dégradent avec un avertissement.
 * Le contenu saisi (titres, textes) n'est jamais journalisé, seulement les identifiants.
 */
class BulletinController extends Controller
{
    use LogsErrors;

    private const BULLETIN_RELATIONS = [
        'weeklyServices.assignedUser',
        'weeklyActivities',
        'spiritualReflections',
    ];

    private const DEFAULT_RECTOR_MESSAGE = 'LE RECTEUR SOUHAITE A NOUS TOUS UNE BONNE SEMAINE !';

    /**
     * Display the current bulletin (public dashboard view).
     */
    public function current()
    {
        $degradedNotice = null;

        try {
            $bulletin = BulletinEdition::with(self::BULLETIN_RELATIONS)
                ->published()
                ->current()
                ->first();

            // If no current bulletin, get the latest published one
            if (! $bulletin) {
                $bulletin = BulletinEdition::with(self::BULLETIN_RELATIONS)
                    ->published()
                    ->latest('week_start')
                    ->first();
            }
        } catch (Throwable $e) {
            $this->logError('bulletin.current.failed', $e);
            $bulletin = null;
            $degradedNotice = 'Le bulletin de la semaine est momentanément indisponible. Veuillez réessayer plus tard.';
        }

        // Get birthdays for this week (BirthdayService ne lève jamais d'exception)
        $birthdays = app(BirthdayService::class)->getWeekBirthdays($bulletin);

        $rectorMessage = config('app.rector_message', self::DEFAULT_RECTOR_MESSAGE);

        return view('bulletin.current', compact('bulletin', 'birthdays', 'rectorMessage', 'degradedNotice'));
    }

    /**
     * Display a listing of all bulletin editions.
     */
    public function index(Request $request)
    {
        $this->authorizeAdminOrCommunicator();

        try {
            $query = BulletinEdition::with('creator');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('edition_number', 'like', "%{$search}%");
                });
            }

            if ($request->filled('year')) {
                $query->where('year', $request->year);
            }

            if ($request->filled('status')) {
                $query->where('is_published', $request->status === 'published');
            }

            $bulletins = $query->orderBy('year', 'desc')
                ->orderBy('edition_number', 'desc')
                ->paginate(15)
                ->withQueryString();

            $years = BulletinEdition::selectRaw('DISTINCT year')->orderBy('year', 'desc')->pluck('year');
        } catch (Throwable $e) {
            $this->logError('bulletin.index.failed', $e, [
                'filters' => $request->only(['year', 'status']),
                'has_search' => $request->filled('search'),
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Impossible de charger la liste des bulletins. Veuillez réessayer plus tard.');
        }

        return view('bulletin.index', compact('bulletins', 'years'));
    }

    /**
     * Show the form for creating a new bulletin edition.
     */
    public function create()
    {
        $this->authorizeAdminOrCommunicator();

        try {
            // Get next edition number for display
            $lastEdition = BulletinEdition::where('year', now()->year)
                ->orderBy('edition_number', 'desc')
                ->first();

            $nextNumber = $lastEdition ? $lastEdition->edition_number + 1 : 1;

            // Suggest week dates (current week or next week)
            $today = Carbon::now();
            $suggestedWeekStart = $today->copy()->startOfWeek();
            $suggestedWeekEnd = $suggestedWeekStart->copy()->endOfWeek();

            // If we're past Wednesday, suggest next week
            if ($today->dayOfWeek > 3) {
                $suggestedWeekStart = $suggestedWeekStart->addWeek();
                $suggestedWeekEnd = $suggestedWeekEnd->addWeek();
            }

            $users = User::active()->orderBy('name')->get();
        } catch (Throwable $e) {
            $this->logError('bulletin.create.failed', $e);

            return redirect()->route('bulletin.index')
                ->with('error', 'Impossible d\'ouvrir le formulaire de création. Veuillez réessayer plus tard.');
        }

        return view('bulletin.create', compact(
            'nextNumber',
            'suggestedWeekStart',
            'suggestedWeekEnd',
            'users'
        ));
    }

    /**
     * Store a newly created bulletin edition.
     */
    public function store(Request $request)
    {
        $this->authorizeAdminOrCommunicator();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'week_start' => 'required|date',
            'week_end' => 'required|date|after_or_equal:week_start',
            'is_published' => 'boolean',
        ]);

        $validated['year'] = Carbon::parse($validated['week_start'])->year;
        $validated['created_by'] = auth()->id();
        $validated['is_published'] = $request->boolean('is_published');

        return $this->attempt('bulletin.store.failed', [
            'week_start' => $validated['week_start'],
            'is_published' => $validated['is_published'],
        ], function () use ($validated) {
            $bulletin = BulletinEdition::create($validated);

            $this->logInfo('bulletin.created', [
                'bulletin_id' => $bulletin->id,
                'edition_number' => $bulletin->edition_number,
                'is_published' => $bulletin->is_published,
            ], 'audit');

            return redirect()
                ->route('bulletin.edit', $bulletin)
                ->with('success', "Edition N{$bulletin->edition_number} creee. Ajoutez maintenant le contenu.");
        }, 'Impossible de créer l\'édition. Veuillez réessayer.');
    }

    /**
     * Display a specific bulletin edition.
     */
    public function show(BulletinEdition $bulletin)
    {
        try {
            $bulletin->load([...self::BULLETIN_RELATIONS, 'creator']);
        } catch (Throwable $e) {
            $this->logError('bulletin.show.failed', $e, ['bulletin_id' => $bulletin->id]);

            return redirect()->route('dashboard')
                ->with('error', 'Impossible d\'afficher ce bulletin. Veuillez réessayer plus tard.');
        }

        $birthdays = app(BirthdayService::class)->getWeekBirthdays($bulletin);
        $rectorMessage = config('app.rector_message', self::DEFAULT_RECTOR_MESSAGE);

        return view('bulletin.show', compact('bulletin', 'birthdays', 'rectorMessage'));
    }

    /**
     * Show the form for editing a bulletin edition.
     */
    public function edit(BulletinEdition $bulletin)
    {
        $this->authorizeAdminOrCommunicator();

        try {
            $bulletin->load(self::BULLETIN_RELATIONS);

            $users = User::active()->orderBy('name')->get();
        } catch (Throwable $e) {
            $this->logError('bulletin.edit.failed', $e, ['bulletin_id' => $bulletin->id]);

            return redirect()->route('bulletin.index')
                ->with('error', 'Impossible d\'ouvrir cette édition. Veuillez réessayer plus tard.');
        }

        $serviceTypes = WeeklyService::SERVICE_TYPES;
        $activityTypes = WeeklyActivity::ACTIVITY_TYPES;
        $reflectionTypes = SpiritualReflection::TYPES;

        return view('bulletin.edit', compact(
            'bulletin',
            'users',
            'serviceTypes',
            'activityTypes',
            'reflectionTypes'
        ));
    }

    /**
     * Update the specified bulletin edition.
     */
    public function update(Request $request, BulletinEdition $bulletin)
    {
        $this->authorizeAdminOrCommunicator();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'week_start' => 'required|date',
            'week_end' => 'required|date|after_or_equal:week_start',
            'is_published' => 'boolean',
        ]);

        $validated['year'] = Carbon::parse($validated['week_start'])->year;
        $validated['is_published'] = $request->boolean('is_published');

        return $this->attempt('bulletin.update.failed', ['bulletin_id' => $bulletin->id], function () use ($bulletin, $validated) {
            $wasPublished = (bool) $bulletin->is_published;

            $bulletin->update($validated);

            $this->logInfo('bulletin.updated', [
                'bulletin_id' => $bulletin->id,
                'changed' => array_keys($bulletin->getChanges()),
            ]);

            if ($wasPublished !== (bool) $bulletin->is_published) {
                $this->logPublication($bulletin);
            }

            return redirect()
                ->route('bulletin.edit', $bulletin)
                ->with('success', 'Edition mise a jour avec succes.');
        }, 'Impossible de mettre à jour l\'édition. Veuillez réessayer.');
    }

    /**
     * Remove the specified bulletin edition.
     */
    public function destroy(BulletinEdition $bulletin)
    {
        $this->authorizeAdminOrCommunicator();

        return $this->attempt('bulletin.destroy.failed', ['bulletin_id' => $bulletin->id], function () use ($bulletin) {
            $bulletin->delete();

            $this->logInfo('bulletin.deleted', [
                'bulletin_id' => $bulletin->id,
                'edition_number' => $bulletin->edition_number,
                'year' => $bulletin->year,
            ], 'audit');

            return redirect()
                ->route('bulletin.index')
                ->with('success', 'Edition supprimee avec succes.');
        }, 'Impossible de supprimer l\'édition. Veuillez réessayer.', false);
    }

    /**
     * Toggle publish status.
     */
    public function togglePublish(BulletinEdition $bulletin)
    {
        $this->authorizeAdminOrCommunicator();

        return $this->attempt('bulletin.publish.failed', [
            'bulletin_id' => $bulletin->id,
            'target_published' => ! $bulletin->is_published,
        ], function () use ($bulletin) {
            $bulletin->update(['is_published' => ! $bulletin->is_published]);

            $this->logPublication($bulletin);

            $status = $bulletin->is_published ? 'publie' : 'depublie';

            return back()->with('success', "Bulletin {$status} avec succes.");
        }, 'Impossible de modifier le statut de publication. Veuillez réessayer.', false);
    }

    // ========================================
    // Weekly Services Management
    // ========================================

    public function storeService(Request $request, BulletinEdition $bulletin)
    {
        $this->authorizeAdminOrCommunicator();

        $validated = $request->validate([
            'service_type' => 'required|string|in:'.implode(',', array_keys(WeeklyService::SERVICE_TYPES)),
            'assigned_user_id' => 'nullable|exists:users,id',
            'assigned_name' => 'nullable|string|max:255',
            'community' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['bulletin_id'] = $bulletin->id;
        $validated['week_start'] = $bulletin->week_start;
        $validated['week_end'] = $bulletin->week_end;
        $validated['is_active'] = true;

        $context = ['bulletin_id' => $bulletin->id, 'service_type' => $validated['service_type']];

        return $this->attempt('bulletin.service.store.failed', $context, function () use ($validated, $context) {
            $service = WeeklyService::create($validated);

            $this->logInfo('bulletin.service.created', $context + ['service_id' => $service->id]);

            return back()->with('success', 'Service ajoute avec succes.');
        }, 'Impossible d\'ajouter le service. Veuillez réessayer.');
    }

    public function updateService(Request $request, WeeklyService $service)
    {
        $this->authorizeAdminOrCommunicator();

        $validated = $request->validate([
            'service_type' => 'required|string|in:'.implode(',', array_keys(WeeklyService::SERVICE_TYPES)),
            'assigned_user_id' => 'nullable|exists:users,id',
            'assigned_name' => 'nullable|string|max:255',
            'community' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $context = ['service_id' => $service->id, 'bulletin_id' => $service->bulletin_id];

        return $this->attempt('bulletin.service.update.failed', $context, function () use ($service, $validated, $context) {
            $service->update($validated);

            $this->logInfo('bulletin.service.updated', $context);

            return back()->with('success', 'Service mis a jour avec succes.');
        }, 'Impossible de mettre à jour le service. Veuillez réessayer.');
    }

    public function destroyService(WeeklyService $service)
    {
        $this->authorizeAdminOrCommunicator();

        $context = ['service_id' => $service->id, 'bulletin_id' => $service->bulletin_id];

        return $this->attempt('bulletin.service.destroy.failed', $context, function () use ($service, $context) {
            $service->delete();

            $this->logInfo('bulletin.service.deleted', $context);

            return back()->with('success', 'Service supprime avec succes.');
        }, 'Impossible de supprimer le service. Veuillez réessayer.', false);
    }

    // ========================================
    // Weekly Activities Management
    // ========================================

    public function storeActivity(StoreWeeklyActivityRequest $request, BulletinEdition $bulletin)
    {
        $validated = $request->validated();
        $validated['is_highlighted'] = $request->boolean('is_highlighted');

        $context = ['bulletin_id' => $bulletin->id, 'type' => $validated['type']];

        return $this->attempt('bulletin.activity.store.failed', $context, function () use ($validated, $bulletin, $context) {
            $activity = new WeeklyActivity($validated);
            $activity->bulletin_id = $bulletin->id;
            $activity->created_by = auth()->id();
            $activity->save();

            $this->logInfo('bulletin.activity.created', $context + ['activity_id' => $activity->id]);

            return back()->with('success', 'Activite ajoutee avec succes.');
        }, 'Impossible d\'ajouter l\'activité. Veuillez réessayer.');
    }

    public function updateActivity(UpdateWeeklyActivityRequest $request, WeeklyActivity $activity)
    {
        $validated = $request->validated();
        $validated['is_highlighted'] = $request->boolean('is_highlighted');

        $context = ['activity_id' => $activity->id, 'bulletin_id' => $activity->bulletin_id];

        return $this->attempt('bulletin.activity.update.failed', $context, function () use ($activity, $validated, $context) {
            $activity->update($validated);

            $this->logInfo('bulletin.activity.updated', $context);

            return back()->with('success', 'Activite mise a jour avec succes.');
        }, 'Impossible de mettre à jour l\'activité. Veuillez réessayer.');
    }

    public function destroyActivity(WeeklyActivity $activity)
    {
        $this->authorizeAdminOrCommunicator();

        $context = ['activity_id' => $activity->id, 'bulletin_id' => $activity->bulletin_id];

        return $this->attempt('bulletin.activity.destroy.failed', $context, function () use ($activity, $context) {
            $activity->delete();

            $this->logInfo('bulletin.activity.deleted', $context);

            return back()->with('success', 'Activite supprimee avec succes.');
        }, 'Impossible de supprimer l\'activité. Veuillez réessayer.', false);
    }

    // ========================================
    // Spiritual Reflections Management
    // ========================================

    public function storeReflection(Request $request, BulletinEdition $bulletin)
    {
        $this->authorizeAdminOrCommunicator();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'source' => 'nullable|string|max:500',
            'type' => 'required|string|in:'.implode(',', array_keys(SpiritualReflection::TYPES)),
        ]);

        $validated['bulletin_id'] = $bulletin->id;
        $validated['week_start'] = $bulletin->week_start;
        $validated['week_end'] = $bulletin->week_end;
        $validated['created_by'] = auth()->id();
        $validated['is_current'] = true;

        $context = ['bulletin_id' => $bulletin->id, 'type' => $validated['type']];

        return $this->attempt('bulletin.reflection.store.failed', $context, function () use ($validated, $bulletin, $context) {
            // Remove current flag from other reflections of same type
            SpiritualReflection::where('type', $validated['type'])
                ->where('bulletin_id', '!=', $bulletin->id)
                ->update(['is_current' => false]);

            $reflection = SpiritualReflection::create($validated);

            $this->logInfo('bulletin.reflection.created', $context + ['reflection_id' => $reflection->id]);

            return back()->with('success', 'Reflexion ajoutee avec succes.');
        }, 'Impossible d\'ajouter la réflexion. Veuillez réessayer.');
    }

    public function updateReflection(Request $request, SpiritualReflection $reflection)
    {
        $this->authorizeAdminOrCommunicator();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'source' => 'nullable|string|max:500',
            'type' => 'required|string|in:'.implode(',', array_keys(SpiritualReflection::TYPES)),
        ]);

        $context = ['reflection_id' => $reflection->id, 'bulletin_id' => $reflection->bulletin_id];

        return $this->attempt('bulletin.reflection.update.failed', $context, function () use ($reflection, $validated, $context) {
            $reflection->update($validated);

            $this->logInfo('bulletin.reflection.updated', $context);

            return back()->with('success', 'Reflexion mise a jour avec succes.');
        }, 'Impossible de mettre à jour la réflexion. Veuillez réessayer.');
    }

    public function destroyReflection(SpiritualReflection $reflection)
    {
        $this->authorizeAdminOrCommunicator();

        $context = ['reflection_id' => $reflection->id, 'bulletin_id' => $reflection->bulletin_id];

        return $this->attempt('bulletin.reflection.destroy.failed', $context, function () use ($reflection, $context) {
            $reflection->delete();

            $this->logInfo('bulletin.reflection.deleted', $context);

            return back()->with('success', 'Reflexion supprimee avec succes.');
        }, 'Impossible de supprimer la réflexion. Veuillez réessayer.', false);
    }

    // ========================================
    // Helpers
    // ========================================

    /**
     * Check if user is admin or communicator.
     */
    protected function authorizeAdminOrCommunicator(): void
    {
        if (! auth()->user()->hasAnyRole([PlatformRole::ADMIN, PlatformRole::COMMUNICATION])) {
            $this->logWarning('bulletin.access.denied', [
                'action' => request()->route()?->getActionMethod(),
            ], 'security');

            abort(403, 'Acces non autorise.');
        }
    }

    /**
     * Exécute une écriture ; en cas d'exception, journalise $event avec le contexte métier
     * et renvoie sur la page précédente avec un message (et la saisie si $withInput).
     */
    private function attempt(string $event, array $context, callable $action, string $errorMessage, bool $withInput = true)
    {
        try {
            return $action();
        } catch (Throwable $e) {
            $this->logError($event, $e, $context);

            $redirect = back()->with('error', $errorMessage);

            return $withInput ? $redirect->withInput() : $redirect;
        }
    }

    /**
     * Trace dans `audit` la publication ou la dépublication d'une édition.
     */
    private function logPublication(BulletinEdition $bulletin): void
    {
        $this->logInfo($bulletin->is_published ? 'bulletin.published' : 'bulletin.unpublished', [
            'bulletin_id' => $bulletin->id,
            'edition_number' => $bulletin->edition_number,
        ], 'audit');
    }
}
