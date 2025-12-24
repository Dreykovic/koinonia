<?php

namespace App\Http\Controllers;

use App\Models\BulletinEdition;
use App\Models\CommunityRequest;
use App\Models\SundayMeal;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleReservation;
use App\Models\WeekMenu;
use Carbon\Carbon;
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
        $birthdays = $this->getWeekBirthdays($bulletin);


        $todaysMenus = WeekMenu::available()
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

    /**
     * Get birthdays for the bulletin's week.
     */
    protected function getWeekBirthdays(?BulletinEdition $bulletin): \Illuminate\Support\Collection
    {
        if (!$bulletin) {
            // Get this week's birthdays if no bulletin
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();
        } else {
            $weekStart = $bulletin->week_start;
            $weekEnd = $bulletin->week_end;
        }

        return User::active()
            ->whereNotNull('birthday')
            ->get()
            ->filter(function ($user) use ($weekStart, $weekEnd) {
                $birthday = Carbon::parse($user->birthday);
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
}





















