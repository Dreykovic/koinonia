<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CommunitiesSeeder::class,
            UserSeeder::class,
            VehicleSeeder::class,
            VehicleReservationSeeder::class,
            SundayMealSeeder::class,
            RepairLocationSeeder::class,
            WeekMenuSeeder::class,
        ]);
    }
}
