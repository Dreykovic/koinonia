<?php

use App\Http\Controllers\VehicleReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API routes for Vehicle Planning (AJAX/Polling)
// Using web middleware for session-based auth with AJAX requests
Route::middleware(['web', 'auth'])->prefix('v1')->group(function () {
    // Vehicles
    Route::get('vehicles', [VehicleReservationController::class, 'getVehicles']);
    Route::get('vehicles/stats', [VehicleReservationController::class, 'getStats']);

    // Reservations
    Route::get('reservations', [VehicleReservationController::class, 'getReservations']);
    Route::post('reservations', [VehicleReservationController::class, 'apiStore']);
    Route::put('reservations/{id}', [VehicleReservationController::class, 'apiUpdate']);
    Route::delete('reservations/{id}', [VehicleReservationController::class, 'apiDestroy']);
});
