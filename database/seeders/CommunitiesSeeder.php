<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommunitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $communities = ['Matungulu', 'Mveng', 'Xavier', 'Loyola', 'Akwaba', 'Tansi', 'Favre'];

        foreach ($communities as $name) {
            DB::table('communities')->insertOrIgnore([
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
