<?php

use App\Enums\PlatformRole;
use App\Http\Controllers\AccountRequestController;
use App\Http\Controllers\Admin\FailedEmailController;
use App\Http\Controllers\Admin\VehicleSettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\CommunityRequestController;
use App\Http\Controllers\CommunityRequestFormController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\MealInscriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\RepairLocationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserProfileVehicleTypeController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WeekMenuController;
use App\Http\Controllers\VehicleReservationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest routes (non-authenticated)
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

    // Account Request (public form)
    Route::get('demande-compte', [AccountRequestController::class, 'create'])->name('account-request.create');
    Route::post('demande-compte', [AccountRequestController::class, 'store'])->name('account-request.store');
    Route::get('demande-compte/confirmation', [AccountRequestController::class, 'success'])->name('account-request.success');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Home - Bulletin Hebdomadaire
    Route::get('/', [BulletinController::class, 'current'])->name('home');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Guide
    Route::get('/guide', [GuideController::class, 'index'])->name('guide');

    // Vehicles Planning (accessible to all authenticated users)
    Route::get('vehicles/planning', [VehicleReservationController::class, 'planning'])->name('vehicles.planning');

    // Vehicle Reservations
    Route::prefix('reservations')->name('reservations.')->group(function () {
        Route::get('/', [VehicleReservationController::class, 'index'])->name('index');
        Route::get('/create', [VehicleReservationController::class, 'create'])->name('create');
        Route::post('/', [VehicleReservationController::class, 'store'])->name('store');
        Route::get('/{reservation}', [VehicleReservationController::class, 'show'])->name('show');
        Route::get('/{reservation}/edit', [VehicleReservationController::class, 'edit'])->name('edit');
        Route::put('/{reservation}', [VehicleReservationController::class, 'update'])->name('update');
        Route::delete('/{reservation}', [VehicleReservationController::class, 'destroy'])->name('destroy');
        Route::post('/{reservation}/approve', [VehicleReservationController::class, 'approve'])->name('approve');
        Route::post('/{reservation}/reject', [VehicleReservationController::class, 'reject'])->name('reject');
    });

    // Vehicles Management (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::resource('vehicles', VehicleController::class)->except(['show']);
        Route::get('vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
        Route::post('vehicles/{vehicle}/toggle-availability', [VehicleController::class, 'toggleAvailability'])->name('vehicles.toggle-availability');
    });

    // Sunday Meals - Member routes
    Route::prefix('meals')->name('meals.')->group(function () {
        Route::get('/', [MealController::class, 'index'])->name('index');
        Route::get('/{meal}', [MealController::class, 'show'])->name('show')->where('meal', '[0-9]+');
        Route::post('/{meal}/register', [MealInscriptionController::class, 'register'])->name('register');
        Route::put('/{meal}/inscription', [MealInscriptionController::class, 'update'])->name('inscription.update');
        Route::delete('/{meal}/unregister', [MealInscriptionController::class, 'unregister'])->name('unregister');
        Route::post('/{meal}/toggle', [MealController::class, 'toggleOpen'])->name('toggle');
        Route::get('/widget-data', [MealController::class, 'widgetData'])->name('widget-data');

        // Admin routes
        Route::middleware('role:admin')->group(function () {
            Route::get('/admin', [MealController::class, 'adminIndex'])->name('admin.index');
            Route::get('/admin/create', [MealController::class, 'create'])->name('admin.create');
            Route::post('/admin', [MealController::class, 'store'])->name('admin.store');
            Route::get('/admin/{meal}/edit', [MealController::class, 'edit'])->name('admin.edit');
            Route::put('/admin/{meal}', [MealController::class, 'update'])->name('admin.update');
            Route::delete('/admin/{meal}', [MealController::class, 'destroy'])->name('admin.destroy');
            Route::get('/admin/{meal}', [MealController::class, 'adminShow'])->name('admin.show');
            Route::post('/admin/{meal}/toggle', [MealController::class, 'toggleOpen'])->name('admin.toggle');
        });
    });

    // Bulletin Hebdomadaire
    Route::prefix('bulletin')->name('bulletin.')->group(function () {
        // Public routes (accessible to all authenticated users)
        Route::get('/current', [BulletinController::class, 'current'])->name('current');

        // Admin & Communication routes (defined BEFORE the show route to avoid conflict)
        Route::middleware('role:admin,communication')->group(function () {
            Route::get('/', [BulletinController::class, 'index'])->name('index');
            Route::get('/create', [BulletinController::class, 'create'])->name('create');
            Route::post('/', [BulletinController::class, 'store'])->name('store');
            Route::get('/{bulletin}/edit', [BulletinController::class, 'edit'])->name('edit');
            Route::put('/{bulletin}', [BulletinController::class, 'update'])->name('update');
            Route::delete('/{bulletin}', [BulletinController::class, 'destroy'])->name('destroy');
            Route::post('/{bulletin}/toggle-publish', [BulletinController::class, 'togglePublish'])->name('toggle-publish');

            // Services
            Route::post('/{bulletin}/services', [BulletinController::class, 'storeService'])->name('services.store');
            Route::put('/services/{service}', [BulletinController::class, 'updateService'])->name('services.update');
            Route::delete('/services/{service}', [BulletinController::class, 'destroyService'])->name('services.destroy');

            // Activities
            Route::post('/{bulletin}/activities', [BulletinController::class, 'storeActivity'])->name('activities.store');
            Route::put('/activities/{activity}', [BulletinController::class, 'updateActivity'])->name('activities.update');
            Route::delete('/activities/{activity}', [BulletinController::class, 'destroyActivity'])->name('activities.destroy');

            // Reflections
            Route::post('/{bulletin}/reflections', [BulletinController::class, 'storeReflection'])->name('reflections.store');
            Route::put('/reflections/{reflection}', [BulletinController::class, 'updateReflection'])->name('reflections.update');
            Route::delete('/reflections/{reflection}', [BulletinController::class, 'destroyReflection'])->name('reflections.destroy');
        });

        // Show route (must be LAST because {bulletin} catches everything)
        Route::get('/{bulletin}', [BulletinController::class, 'show'])->name('show')->where('bulletin', '[0-9]+');
    });

    // Users Management (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    });


    // Profile
    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Community Requests (Reparations et Achats)
    Route::prefix('demandes')->name('community-requests.')->group(function () {
        // Accessible to all authenticated users (controller filters by ownership)
        Route::get('/', [CommunityRequestController::class, 'index'])->name('index');

        // Admin-only routes (defined BEFORE wildcard to avoid conflict)
        Route::middleware(roleMiddleware([PlatformRole::ADMIN]))->group(function () {
            Route::get('/statistics', [CommunityRequestController::class, 'statistics'])->name('statistics');
            Route::get('/export', [CommunityRequestController::class, 'export'])->name('export');
            Route::put('/{communityRequest}', [CommunityRequestController::class, 'update'])->name('update');
            Route::delete('/{communityRequest}', [CommunityRequestController::class, 'destroy'])->name('destroy');
        });

        Route::get('/{communityRequest}', [CommunityRequestController::class, 'show'])->name('show');
    });

    // Repair Locations & Communities Management (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::resource('repair-locations', RepairLocationController::class)->except(['create', 'show', 'edit']);
        Route::resource('communities', CommunityController::class)->except(['create', 'show', 'edit']);

        // Account Requests Management
        Route::prefix('account-requests')->name('account-requests.')->group(function () {
            Route::get('/', [AccountRequestController::class, 'index'])->name('index');
            Route::get('/{accountRequest}', [AccountRequestController::class, 'show'])->name('show');
            Route::post('/{accountRequest}/approve', [AccountRequestController::class, 'approve'])->name('approve');
            Route::post('/{accountRequest}/reject', [AccountRequestController::class, 'reject'])->name('reject');
        });

        // Failed Emails Management
        Route::prefix('admin/failed-emails')->name('admin.failed-emails.')->group(function () {
            Route::get('/', [FailedEmailController::class, 'index'])->name('index');
            Route::post('/{failedEmail}/resolve', [FailedEmailController::class, 'resolve'])->name('resolve');
            Route::delete('/{failedEmail}', [FailedEmailController::class, 'destroy'])->name('destroy');
        });

        // Vehicle Settings Management
        Route::prefix('admin/vehicle-settings')->name('admin.vehicle-settings.')->group(function () {
            Route::get('/', [VehicleSettingController::class, 'edit'])->name('edit');
            Route::put('/', [VehicleSettingController::class, 'update'])->name('update');
        });
    });

    // Community Requests Form (authenticated users)
    Route::prefix('demande')->name('community-requests.form.')->group(function () {
        Route::get('/', [CommunityRequestFormController::class, 'step1'])->name('step1');
        Route::post('/', [CommunityRequestFormController::class, 'postStep1'])->name('step1.post');
        Route::get('/reparation', [CommunityRequestFormController::class, 'step2Repair'])->name('step2.repair');
        Route::post('/reparation', [CommunityRequestFormController::class, 'postStep2Repair'])->name('step2.repair.post');
        Route::get('/achat', [CommunityRequestFormController::class, 'step2Purchase'])->name('step2.purchase');
        Route::post('/achat', [CommunityRequestFormController::class, 'postStep2Purchase'])->name('step2.purchase.post');
        Route::get('/confirmation', [CommunityRequestFormController::class, 'success'])->name('success');
        Route::get('/qr-download', [CommunityRequestFormController::class, 'downloadQR'])->name('qr-download');
    });



    // Week menu - Member routes
    Route::prefix('week-menu')->name('week-menus.')->group(function () {
        Route::get('/', [WeekMenuController::class, 'index'])->name('index');

        // Admin routes
        Route::middleware('role:admin')->group(function () {
            Route::get('/admin/edit', [WeekMenuController::class, 'edit'])
                ->name('admin.edit');

            Route::patch('/admin/active-week', [WeekMenuController::class, 'setActiveWeek'])
                ->name('admin.active-week');

            Route::put('/admin/{weekMenu}/update', [WeekMenuController::class, 'update'])
                ->name('admin.update');

            Route::patch('/admin/{weekMenu}/toggle', [WeekMenuController::class, 'toggle'])
                ->name('admin.toggle');
        });
    });


    // uSER pROFILE & vehicle type
    Route::middleware(['role:admin'])->group(function () {
        Route::prefix('user-profile-vehicle-types')->name('user-profile-vehicle-types.')->group(function () {
        Route::get('/', [UserProfileVehicleTypeController::class, 'index'])
            ->name('index');
        Route::post('/', [UserProfileVehicleTypeController::class, 'store'])
            ->name('store');
        Route::put('/{userProfile}/{vehicleType}', [UserProfileVehicleTypeController::class, 'update'])
            ->name('update');
        Route::delete('/{userProfile}/{vehicleType}', [UserProfileVehicleTypeController::class, 'destroy'])
            ->name('destroy');
        });


    });
});





















