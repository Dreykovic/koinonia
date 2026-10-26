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
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the dashboard.
     */
    public function index()
    {
        $user = auth()->user();

        // Get upcoming meal
        $upcomingMeal = SundayMeal::upcoming()->first();
        $userInscription = null;
        if ($upcomingMeal) {
            $userInscription = $upcomingMeal->inscriptions()
                ->where('user_id', $user->id)
                ->whereNull('cancelled_at')
                ->first();
        }

        // Get user's upcoming reservations
        $myReservations = VehicleReservation::with('vehicle')
            ->where('user_id', $user->id)
            ->where('reservation_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('reservation_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        // Get user's pending community requests
        $myRequests = CommunityRequest::where('email', $user->email)
            ->pending()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Stats for admin
        $stats = null;
        if ($user->isAdmin()) {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::active()->count(),
                'pending_reservations' => VehicleReservation::byStatus('pending')->count(),
                'pending_requests' => CommunityRequest::pending()->count(),
                'available_vehicles' => Vehicle::available()->count(),
                'reservations_today' => VehicleReservation::whereDate('reservation_date', now()->toDateString())->count(),
            ];
        } else {
            $stats = [
                'available_vehicles' => Vehicle::available()->count(),
                'reservations_today' => VehicleReservation::whereDate('reservation_date', now()->toDateString())
                    ->where('user_id', $user->id)->count(),
                'pending_requests' => CommunityRequest::where('email', $user->email)->pending()->count(),
            ];
        }

        // Get current bulletin
        $bulletin = BulletinEdition::with([
            'weeklyServices.assignedUser',
            'weeklyActivities',
            'spiritualReflections',
        ])
        ->published()
        ->current()
        ->first();

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
        $birthdays = app(BirthdayService::class)->getWeekBirthdays($bulletin);


        $todaysMenus = WeekMenu::available()
            ->forActiveWeek()
            ->today()
            ->ordered()
            ->get();
        // dd($myRequests);
        // dd($todaysMenus);
        return view('dashboard.index', compact(
            'upcomingMeal',
            'userInscription',
            'myReservations',
            'myRequests',
            'stats',
            'bulletin',
            'birthdays',
            'todaysMenus',
        ));
    }

}






















