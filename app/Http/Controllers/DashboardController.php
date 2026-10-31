<?php

namespace App\Http\Controllers;

use App\Models\BulletinEdition;
use App\Models\CommunityRequest;
use App\Models\SundayMeal;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleReservation;
use App\Models\WeekMenu;
use App\Services\BirthdayService;
use App\Traits\LogsErrors;
use Throwable;

class DashboardController extends Controller
{
    use LogsErrors;

    /**
     * Libellés des widgets, utilisés dans l'avertissement affiché quand l'un d'eux échoue.
     */
    private const WIDGET_LABELS = [
        'meal' => 'repas du dimanche',
        'reservations' => 'réservations',
        'requests' => 'demandes',
        'stats' => 'statistiques',
        'bulletin' => 'bulletin',
        'birthdays' => 'anniversaires',
        'menus' => 'menu du jour',
    ];

    /**
     * Widgets dont le chargement a échoué pendant cette requête.
     *
     * @var array<int, string>
     */
    private array $failedWidgets = [];

    /**
     * Show the dashboard.
     *
     * Chaque widget est chargé indépendamment : un échec est journalisé
     * (dashboard.widget.failed) et remplacé par une valeur vide, la page reste affichée.
     */
    public function index()
    {
        $user = auth()->user();

        // Get upcoming meal
        [$upcomingMeal, $userInscription] = $this->widget('meal', function () use ($user) {
            $meal = SundayMeal::upcoming()->first();
            $inscription = $meal?->inscriptions()
                ->where('user_id', $user->id)
                ->whereNull('cancelled_at')
                ->first();

            return [$meal, $inscription];
        }, [null, null]);

        // Get user's upcoming reservations
        $myReservations = $this->widget('reservations', fn () => VehicleReservation::with('vehicle')
            ->where('user_id', $user->id)
            ->where('reservation_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('reservation_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get(), collect());

        // Get user's pending community requests
        $myRequests = $this->widget('requests', fn () => CommunityRequest::where('email', $user->email)
            ->pending()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(), collect());

        // Stats (la vue affiche 0 pour une clé absente)
        $stats = $this->widget('stats', fn () => $user->isAdmin()
            ? [
                'total_users' => User::count(),
                'active_users' => User::active()->count(),
                'pending_reservations' => VehicleReservation::byStatus('pending')->count(),
                'pending_requests' => CommunityRequest::pending()->count(),
                'available_vehicles' => Vehicle::available()->count(),
                'reservations_today' => VehicleReservation::whereDate('reservation_date', now()->toDateString())->count(),
            ]
            : [
                'available_vehicles' => Vehicle::available()->count(),
                'reservations_today' => VehicleReservation::whereDate('reservation_date', now()->toDateString())
                    ->where('user_id', $user->id)->count(),
                'pending_requests' => CommunityRequest::where('email', $user->email)->pending()->count(),
            ], []);

        // Get current bulletin (or the latest published one)
        $bulletin = $this->widget('bulletin', function () {
            $relations = ['weeklyServices.assignedUser', 'weeklyActivities', 'spiritualReflections'];

            return BulletinEdition::with($relations)->published()->current()->first()
                ?? BulletinEdition::with($relations)->published()->latest('week_start')->first();
        }, null);

        // Get birthdays for this week
        $birthdays = $this->widget('birthdays', fn () => app(BirthdayService::class)->getWeekBirthdays($bulletin), collect());

        $todaysMenus = $this->widget('menus', fn () => WeekMenu::available()
            ->forActiveWeek()
            ->today()
            ->ordered()
            ->get(), collect());

        $degradedNotice = $this->degradedNotice();

        return view('dashboard.index', compact(
            'upcomingMeal',
            'userInscription',
            'myReservations',
            'myRequests',
            'stats',
            'bulletin',
            'birthdays',
            'todaysMenus',
            'degradedNotice',
        ));
    }

    /**
     * Charge les données d'un widget ; en cas d'exception, journalise et renvoie $fallback.
     */
    private function widget(string $name, callable $loader, mixed $fallback): mixed
    {
        try {
            return $loader();
        } catch (Throwable $e) {
            $this->failedWidgets[] = $name;
            $this->logError('dashboard.widget.failed', $e, ['widget' => $name]);

            return $fallback;
        }
    }

    private function degradedNotice(): ?string
    {
        if ($this->failedWidgets === []) {
            return null;
        }

        $this->logWarning('dashboard.degraded', ['failed_widgets' => $this->failedWidgets]);

        $labels = array_map(fn ($name) => self::WIDGET_LABELS[$name] ?? $name, $this->failedWidgets);

        return 'Certaines informations sont momentanément indisponibles ('.implode(', ', $labels).'). Veuillez réessayer plus tard.';
    }
}
