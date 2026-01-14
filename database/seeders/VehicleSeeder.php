<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        // Vehicule Sante
        Vehicle::create([
            'name' => 'Vehicule Sante',
            'license_plate' => 'LT-001-SN',
            'type' => 'sante',
            'capacity' => 5,
            'description' => 'Vehicule reserve aux urgences medicales et au personnel de sante.',
            'is_available' => true,
        ]);

        // Vehicule Formation
        Vehicle::create([
            'name' => 'Vehicule Formation',
            'license_plate' => 'LT-002-FM',
            'type' => 'formation',
            'capacity' => 7,
            'description' => 'Vehicule reserve aux formateurs pour leurs deplacements professionnels.',
            'is_available' => true,
        ]);

        // Vehicules Communautaires
        Vehicle::create([
            'name' => 'Toyota Corolla',
            'license_plate' => 'LT-003-CM',
            'type' => 'communautaire',
            'capacity' => 5,
            'description' => 'Berline pour les deplacements quotidiens.',
            'is_available' => true,
        ]);

        Vehicle::create([
            'name' => 'Peugeot 308',
            'license_plate' => 'LT-004-CM',
            'type' => 'communautaire',
            'capacity' => 5,
            'description' => 'Berline compacte pour les courses en ville.',
            'is_available' => true,
        ]);

        Vehicle::create([
            'name' => 'Renault Kangoo',
            'license_plate' => 'LT-005-CM',
            'type' => 'communautaire',
            'capacity' => 5,
            'description' => 'Utilitaire pour les transports de materiel.',
            'is_available' => true,
        ]);
    }
}
