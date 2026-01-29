<?php

namespace Database\Seeders;

use App\Models\RepairLocation;
use Illuminate\Database\Seeder;

class RepairLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            'Chambre',
            'Cuisine/refectoire',
            'Salle Tele',
            'Toilettes',
            'Chapelle',
            'Salle informatique',
            'Parloir',
            'autre'
        ];

        foreach ($locations as $name) {
            RepairLocation::firstOrCreate(['name' => $name]);
        }
    }
}
