<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Donnees de reference issues de la base de production : communautes, profils, vehicules, lieux de reparation, parametres.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('communities')->upsert([
            ['id' => 1, 'name' => 'Matungulu', 'description' => null, 'is_active' => 1, 'created_at' => '2026-01-23 17:14:28', 'updated_at' => '2026-01-23 17:14:28'],
            ['id' => 2, 'name' => 'Mveng', 'description' => null, 'is_active' => 1, 'created_at' => '2026-01-23 17:14:28', 'updated_at' => '2026-01-23 17:14:28'],
            ['id' => 3, 'name' => 'Xavier', 'description' => null, 'is_active' => 1, 'created_at' => '2026-01-23 17:14:28', 'updated_at' => '2026-01-23 17:14:28'],
            ['id' => 4, 'name' => 'Loyola', 'description' => null, 'is_active' => 1, 'created_at' => '2026-01-23 17:14:28', 'updated_at' => '2026-01-23 17:14:28'],
            ['id' => 5, 'name' => 'Akwaba', 'description' => null, 'is_active' => 1, 'created_at' => '2026-01-23 17:14:28', 'updated_at' => '2026-01-23 17:14:28'],
            ['id' => 6, 'name' => 'Tansi', 'description' => null, 'is_active' => 1, 'created_at' => '2026-01-23 17:14:28', 'updated_at' => '2026-01-23 17:14:28'],
            ['id' => 7, 'name' => 'Favre', 'description' => null, 'is_active' => 1, 'created_at' => '2026-01-23 17:14:28', 'updated_at' => '2026-01-23 17:14:28'],
        ], ['id']);

        DB::table('vehicle_types')->upsert([
            ['id' => 1, 'name' => 'sante', 'description' => null, 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 2, 'name' => 'formation', 'description' => null, 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 3, 'name' => 'communautaire', 'description' => null, 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
        ], ['id']);

        DB::table('user_profiles')->upsert([
            ['id' => 1, 'name' => 'administrateur', 'description' => null, 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 2, 'name' => 'sante', 'description' => null, 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 3, 'name' => 'etudiant', 'description' => null, 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 4, 'name' => 'formateur', 'description' => null, 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 5, 'name' => 'chauffeur', 'description' => null, 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
        ], ['id']);

        DB::table('user_profile_vehicle_types')->upsert([
            ['id' => 1, 'user_profile_id' => 1, 'vehicle_type_id' => 1, 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 2, 'user_profile_id' => 1, 'vehicle_type_id' => 2, 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 3, 'user_profile_id' => 1, 'vehicle_type_id' => 3, 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 4, 'user_profile_id' => 2, 'vehicle_type_id' => 1, 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 5, 'user_profile_id' => 3, 'vehicle_type_id' => 3, 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 6, 'user_profile_id' => 4, 'vehicle_type_id' => 2, 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 8, 'user_profile_id' => 2, 'vehicle_type_id' => 3, 'is_active' => 1, 'created_at' => '2026-01-24 07:08:43', 'updated_at' => '2026-01-24 07:08:43'],
            ['id' => 9, 'user_profile_id' => 5, 'vehicle_type_id' => 3, 'is_active' => 1, 'created_at' => '2026-03-02 21:21:18', 'updated_at' => '2026-03-02 21:21:18'],
        ], ['id']);

        DB::table('vehicles')->upsert([
            ['id' => 1, 'name' => 'Toyota Urban Cruiser', 'license_plate' => null, 'vehicle_type_id' => 1, 'capacity' => 5, 'is_available' => 1, 'description' => 'Véhicule affecté au Service Santé', 'created_at' => '2026-01-23 17:15:04', 'updated_at' => '2026-06-12 13:25:09'],
            ['id' => 2, 'name' => 'Hyundai Tucson', 'license_plate' => null, 'vehicle_type_id' => 2, 'capacity' => 5, 'is_available' => 1, 'description' => 'Véhicule affecté au Service Formateurs', 'created_at' => '2026-01-23 17:15:04', 'updated_at' => '2026-10-07 08:05:07'],
            ['id' => 3, 'name' => 'Suzuki Ciaz', 'license_plate' => null, 'vehicle_type_id' => 2, 'capacity' => 5, 'is_available' => 1, 'description' => 'Véhicule affecté au Service Formateurs', 'created_at' => '2026-01-23 17:15:04', 'updated_at' => '2026-11-07 18:09:34'],
            ['id' => 4, 'name' => 'Suzuki Ertiga', 'license_plate' => null, 'vehicle_type_id' => 3, 'capacity' => 7, 'is_available' => 1, 'description' => 'Véhicule affecté au Service Formation', 'created_at' => '2026-01-23 17:15:04', 'updated_at' => '2026-10-08 21:51:46'],
            ['id' => 5, 'name' => 'Toyota Starlet Cross', 'license_plate' => null, 'vehicle_type_id' => 3, 'capacity' => 5, 'is_available' => 1, 'description' => 'Véhicule affecté au Service Formation', 'created_at' => '2026-01-23 17:15:04', 'updated_at' => '2026-01-23 17:15:04'],
            ['id' => 6, 'name' => 'Suzuki Across', 'license_plate' => null, 'vehicle_type_id' => 2, 'capacity' => 5, 'is_available' => 0, 'description' => null, 'created_at' => '2026-11-08 18:31:55', 'updated_at' => '2026-11-08 18:32:03'],
        ], ['id']);

        DB::table('repair_locations')->upsert([
            ['id' => 1, 'name' => 'Chambre', 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 2, 'name' => 'Cuisine/refectoire', 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 3, 'name' => 'Salle Tele', 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 4, 'name' => 'Toilettes', 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 5, 'name' => 'Chapelle', 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 6, 'name' => 'Salle informatique', 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 7, 'name' => 'Parloir', 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 8, 'name' => 'autre', 'is_active' => 1, 'created_at' => null, 'updated_at' => null],
        ], ['id']);

        DB::table('vehicle_settings')->upsert([
            ['id' => 1, 'opening_hour' => '05:00:00', 'closing_hour' => '23:59:00', 'max_future_days' => 30, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-03-24 21:05:05'],
        ], ['id']);
    }
}