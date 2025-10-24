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
use Carbon\Carbon;
use Illuminate\Http\Request;

class BulletinController extends Controller
{
    /**
     * Display the current bulletin (public dashboard view).
     */
    public function current()
    {
        $bulletin = BulletinEdition::with([
            'weeklyServices.assignedUser',
            'weeklyActivities',
            'spiritualReflections',
        ])
        ->published()
        ->current()
        ->first();

        // If no current bulletin, get the latest published one
        if (!$bulletin) {
            $bulletin = BulletinEdition::with([
                'weeklyServices.assignedUser',
                'weeklyActivities',
                'spiritualReflections',
            ])
            ->published()
            ->latest('week_start')
            ->first();
        }

        // Get birthdays for this week
        $birthdays = $this->getWeekBirthdays($bulletin);

        // Get rector message (stored in settings or default)
        $rectorMessage = config('app.rector_message', 'LE RECTEUR SOUHAITE A NOUS TOUS UNE BONNE SEMAINE !');

        return view('bulletin.current', compact('bulletin', 'birthdays', 'rectorMessage'));
    }

    /**
     * Display a listing of all bulletin editions.
     */
    public function index(Request $request)
    {
        $this->authorizeAdminOrCommunicator();

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

        return view('bulletin.index', compact('bulletins', 'years'));
    }

    /**
     * Show the form for creating a new bulletin edition.
     */
    public function create()
    {
        $this->authorizeAdminOrCommunicator();

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

        $bulletin = BulletinEdition::create($validated);

        return redirect()
            ->route('bulletin.edit', $bulletin)
            ->with('success', "Edition N{$bulletin->edition_number} creee. Ajoutez maintenant le contenu.");
    }

    /**
     * Display a specific bulletin edition.
     */
    public function show(BulletinEdition $bulletin)
    {
        $bulletin->load([
            'weeklyServices.assignedUser',
            'weeklyActivities',
            'spiritualReflections',
            'creator',
        ]);

        $birthdays = $this->getWeekBirthdays($bulletin);
        $rectorMessage = config('app.rector_message', 'LE RECTEUR SOUHAITE A NOUS TOUS UNE BONNE SEMAINE !');

        return view('bulletin.show', compact('bulletin', 'birthdays', 'rectorMessage'));
    }

    /**
     * Show the form for editing a bulletin edition.
     */
    public function edit(BulletinEdition $bulletin)
    {
        $this->authorizeAdminOrCommunicator();

        $bulletin->load([
            'weeklyServices.assignedUser',
            'weeklyActivities',
            'spiritualReflections',
        ]);

        $users = User::active()->orderBy('name')->get();
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

        $bulletin->update($validated);

        return redirect()
            ->route('bulletin.edit', $bulletin)
            ->with('success', 'Edition mise a jour avec succes.');
    }

    /**
     * Remove the specified bulletin edition.
     */
    public function destroy(BulletinEdition $bulletin)
    {
        $this->authorizeAdminOrCommunicator();

        $bulletin->delete();

        return redirect()
            ->route('bulletin.index')
            ->with('success', 'Edition supprimee avec succes.');
    }

    /**
     * Toggle publish status.
     */
    public function togglePublish(BulletinEdition $bulletin)
    {
        $this->authorizeAdminOrCommunicator();

        $bulletin->update(['is_published' => !$bulletin->is_published]);

        $status = $bulletin->is_published ? 'publie' : 'depublie';
        return back()->with('success', "Bulletin {$status} avec succes.");
    }

    // ========================================
    // Weekly Services Management
    // ========================================

    public function storeService(Request $request, BulletinEdition $bulletin)
    {
        $this->authorizeAdminOrCommunicator();

        $validated = $request->validate([
            'service_type' => 'required|string|in:' . implode(',', array_keys(WeeklyService::SERVICE_TYPES)),
            'assigned_user_id' => 'nullable|exists:users,id',
            'assigned_name' => 'nullable|string|max:255',
            'community' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['bulletin_id'] = $bulletin->id;
        $validated['week_start'] = $bulletin->week_start;
        $validated['week_end'] = $bulletin->week_end;
        $validated['is_active'] = true;

        WeeklyService::create($validated);

        return back()->with('success', 'Service ajoute avec succes.');
    }

    public function updateService(Request $request, WeeklyService $service)
    {
        $this->authorizeAdminOrCommunicator();

        $validated = $request->validate([
            'service_type' => 'required|string|in:' . implode(',', array_keys(WeeklyService::SERVICE_TYPES)),
            'assigned_user_id' => 'nullable|exists:users,id',
            'assigned_name' => 'nullable|string|max:255',
            'community' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $service->update($validated);

        return back()->with('success', 'Service mis a jour avec succes.');
    }

    public function destroyService(WeeklyService $service)
    {
        $this->authorizeAdminOrCommunicator();

        $service->delete();

        return back()->with('success', 'Service supprime avec succes.');
    }

    // ========================================
    // Weekly Activities Management
    // ========================================

    public function storeActivity(StoreWeeklyActivityRequest $request, BulletinEdition $bulletin)
    {
        $validated = $request->validated();
        $validated['is_highlighted'] = $request->boolean('is_highlighted');

        $activity = new WeeklyActivity($validated);
        $activity->bulletin_id = $bulletin->id;
        $activity->created_by = auth()->id();
        $activity->save();

        return back()->with('success', 'Activite ajoutee avec succes.');
    }

    public function updateActivity(UpdateWeeklyActivityRequest $request, WeeklyActivity $activity)
    {
        $validated = $request->validated();
        $validated['is_highlighted'] = $request->boolean('is_highlighted');

        $activity->update($validated);

        return back()->with('success', 'Activite mise a jour avec succes.');
    }

    public function destroyActivity(WeeklyActivity $activity)
    {
        $this->authorizeAdminOrCommunicator();

        $activity->delete();

        return back()->with('success', 'Activite supprimee avec succes.');
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
            'type' => 'required|string|in:' . implode(',', array_keys(SpiritualReflection::TYPES)),
        ]);

        $validated['bulletin_id'] = $bulletin->id;
        $validated['week_start'] = $bulletin->week_start;
        $validated['week_end'] = $bulletin->week_end;
        $validated['created_by'] = auth()->id();
        $validated['is_current'] = true;

        // Remove current flag from other reflections of same type
        SpiritualReflection::where('type', $validated['type'])
            ->where('bulletin_id', '!=', $bulletin->id)
            ->update(['is_current' => false]);

        SpiritualReflection::create($validated);

        return back()->with('success', 'Reflexion ajoutee avec succes.');
    }

    public function updateReflection(Request $request, SpiritualReflection $reflection)
    {
        $this->authorizeAdminOrCommunicator();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'source' => 'nullable|string|max:500',
            'type' => 'required|string|in:' . implode(',', array_keys(SpiritualReflection::TYPES)),
        ]);

        $reflection->update($validated);

        return back()->with('success', 'Reflexion mise a jour avec succes.');
    }

    public function destroyReflection(SpiritualReflection $reflection)
    {
        $this->authorizeAdminOrCommunicator();

        $reflection->delete();

        return back()->with('success', 'Reflexion supprimee avec succes.');
    }

    // ========================================
    // Helpers
    // ========================================

    /**
     * Get birthdays for the bulletin's week.
     */
    protected function getWeekBirthdays(?BulletinEdition $bulletin): \Illuminate\Support\Collection
    {
        if (!$bulletin) {
            return collect();
        }

        $weekStart = $bulletin->week_start;
        $weekEnd = $bulletin->week_end;

        return User::active()
            ->whereNotNull('birthday')
            ->get()
            ->filter(function ($user) use ($weekStart, $weekEnd) {
                $birthday = Carbon::parse($user->birthday);
                // Check if birthday falls within the week (ignore year)
                $birthdayThisYear = $birthday->copy()->year($weekStart->year);
                return $birthdayThisYear->between($weekStart, $weekEnd);
            })
            ->map(function ($user) use ($weekStart) {
                $birthday = Carbon::parse($user->birthday);
                $birthdayThisYear = $birthday->copy()->year($weekStart->year);
                $user->birthday_day = $birthdayThisYear->locale('fr')->isoFormat('dddd D');
                return $user;
            })
            ->sortBy(function ($user) {
                return Carbon::parse($user->birthday)->format('m-d');
            });
    }

    /**
     * Check if user is admin.
     */
    protected function authorizeAdminOrCommunicator(): void
    {
        if (!auth()->user()->hasAnyRole([PlatformRole::ADMIN,PlatformRole::COMMUNICATION]) ){
            abort(403, 'Acces non autorise.');
        }
    }
}




