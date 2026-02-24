<?php

namespace Database\Seeders;

use App\Models\Community;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Recuperer les IDs des communautes
        $communities = Community::pluck('id', 'name');

        // Admin (Recteur)
        User::updateOrCreate(
            ['email' => 'recteur@itcj.org'],
            [
                'name' => 'Jean-Marie NDONGO',
                'password' => Hash::make('password'),
                'roles' => 'admin',
                'community_id' => $communities['Matungulu'] ?? null,
                'room_number' => 'R01',
                'phone' => '+237 6 99 00 00 01',
                'is_active' => true,
                'title_prefix' => 'P.',
                'birthday' => '1965-03-15',
            ]
        );


        $this->command->info('');
        $this->command->info('=== Utilisateurs crees ===');
        $this->command->info('Admin: recteur@itcj.org / password');
        $this->command->info('Communication: communication@itcj.org / password');
        $this->command->info('Sante: sante@itcj.org / password');
        $this->command->info('Membres: [prenom].[nom]@itcj.org / password');
        $this->command->info('');
    }
}







