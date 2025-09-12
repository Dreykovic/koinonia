<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleReservation;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class VehicleReservationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $membres = User::where('role', 'membre')->take(5)->get();
        $sante = User::where('role', 'sante')->first();
        $formateur = User::where('role', 'formateur')->first();

        $vehiculeSante = Vehicle::where('type', 'sante')->first();
        $vehiculeFormation = Vehicle::where('type', 'formation')->first();
        $vehiculesCommunautaires = Vehicle::where('type', 'communautaire')->get();

        $today = Carbon::today();
        $destinations = ['Centre-ville', 'Aeroport', 'Hopital', 'Universite', 'Marche central', 'Gare routiere'];

        // Reservations pour aujourd'hui
        VehicleReservation::create([
            'vehicle_id' => $vehiculesCommunautaires[0]->id,
            'user_id' => $membres[0]->id,
            'reservation_date' => $today,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'destination' => 'Centre-ville',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        VehicleReservation::create([
            'vehicle_id' => $vehiculesCommunautaires[0]->id,
            'user_id' => $membres[1]->id,
            'reservation_date' => $today,
            'start_time' => '14:00',
            'end_time' => '17:00',
            'destination' => 'Aeroport',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        VehicleReservation::create([
            'vehicle_id' => $vehiculeSante->id,
            'user_id' => $sante->id,
            'reservation_date' => $today,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'destination' => 'Hopital Central',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        VehicleReservation::create([
            'vehicle_id' => $vehiculeFormation->id,
            'user_id' => $formateur->id,
            'reservation_date' => $today,
            'start_time' => '10:00',
            'end_time' => '13:00',
            'destination' => 'Universite Catholique',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        // Reservations pour demain
        $tomorrow = $today->copy()->addDay();

        VehicleReservation::create([
            'vehicle_id' => $vehiculesCommunautaires[1]->id,
            'user_id' => $membres[2]->id,
            'reservation_date' => $tomorrow,
            'start_time' => '07:00',
            'end_time' => '09:00',
            'destination' => 'Marche central',
            'status' => 'pending',
        ]);

        VehicleReservation::create([
            'vehicle_id' => $vehiculesCommunautaires[2]->id,
            'user_id' => $membres[3]->id,
            'reservation_date' => $tomorrow,
            'start_time' => '15:00',
            'end_time' => '18:00',
            'destination' => 'Gare routiere',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'notes' => 'Accueil de visiteurs',
        ]);

        // Reservation annulee
        VehicleReservation::create([
            'vehicle_id' => $vehiculesCommunautaires[0]->id,
            'user_id' => $membres[4]->id,
            'reservation_date' => $today->copy()->subDay(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'destination' => 'Centre-ville',
            'status' => 'cancelled',
            'notes' => 'Annule par le demandeur',
        ]);
    }
}
