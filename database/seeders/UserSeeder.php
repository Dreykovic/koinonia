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
                'role' => 'admin',
                'community_id' => $communities['Matungulu'] ?? null,
                'room_number' => 'R01',
                'phone' => '+237 6 99 00 00 01',
                'is_active' => true,
                'title_prefix' => 'P.',
                'birthday' => '1965-03-15',
            ]
        );

        // Membres Communaute Xavier
        $xavier = [
            ['name' => 'Jean-Baptiste FONGANG', 'prefix' => 'S.', 'room' => 'X01', 'birthday' => '1990-06-22'],
            ['name' => 'Hermann OLIVIER', 'prefix' => 'S.', 'room' => 'X02', 'birthday' => '1991-01-10'],
            ['name' => 'Flavien ZOLABI', 'prefix' => 'P.', 'room' => 'X03', 'birthday' => '1975-12-10'],
            ['name' => 'Michel TRAORE', 'prefix' => 'S.', 'room' => 'X04', 'birthday' => '1993-02-28'],
            ['name' => 'Serge ABEGA', 'prefix' => 'S.', 'room' => 'X05', 'birthday' => '1994-07-14'],
        ];

        foreach ($xavier as $i => $m) {
            User::updateOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $m['name'])) . '@itcj.org'],
                [
                    'name' => $m['name'],
                    'password' => Hash::make('password'),
                    'role' => 'membre',
                    'community_id' => $communities['Xavier'] ?? null,
                    'room_number' => $m['room'],
                    'phone' => '+237 6 ' . rand(70, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . str_pad($i + 10, 2, '0', STR_PAD_LEFT),
                    'is_active' => true,
                    'title_prefix' => $m['prefix'],
                    'birthday' => $m['birthday'],
                ]
            );
        }

        // Membres Communaute Loyola
        $loyola = [
            ['name' => 'Toussaint MBARGA', 'prefix' => 'S.', 'room' => 'L01', 'birthday' => '1988-11-01'],
            ['name' => 'Luc LADOLIEN', 'prefix' => 'S.', 'room' => 'L02', 'birthday' => '1991-12-25'],
            ['name' => 'Emmanuel KOUASSI', 'prefix' => 'S.', 'room' => 'L03', 'birthday' => '1989-08-14'],
            ['name' => 'Paul EBOUE', 'prefix' => 'Fr.', 'room' => 'L04', 'birthday' => '1996-04-05'],
            ['name' => 'Mathieu ESSOMBA', 'prefix' => 'S.', 'room' => 'L05', 'birthday' => '1992-09-30'],
        ];

        foreach ($loyola as $i => $m) {
            User::updateOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $m['name'])) . '@itcj.org'],
                [
                    'name' => $m['name'],
                    'password' => Hash::make('password'),
                    'role' => 'membre',
                    'community_id' => $communities['Loyola'] ?? null,
                    'room_number' => $m['room'],
                    'phone' => '+237 6 ' . rand(70, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . str_pad($i + 20, 2, '0', STR_PAD_LEFT),
                    'is_active' => true,
                    'title_prefix' => $m['prefix'],
                    'birthday' => $m['birthday'],
                ]
            );
        }

        // Membres Communaute Akwaba
        $akwaba = [
            ['name' => 'Alvin MULINGE', 'prefix' => 'S.', 'room' => 'A01', 'birthday' => '1992-07-18'],
            ['name' => 'Pierre NDOUMBE', 'prefix' => 'Fr.', 'room' => 'A02', 'birthday' => '1995-05-05'],
            ['name' => 'Joseph DIALLO', 'prefix' => 'P.', 'room' => 'A03', 'birthday' => '1968-09-20'],
            ['name' => 'Andre TAMBO', 'prefix' => 'S.', 'room' => 'A04', 'birthday' => '1990-03-17'],
            ['name' => 'Bruno BIYA', 'prefix' => 'S.', 'room' => 'A05', 'birthday' => '1993-11-22'],
        ];

        foreach ($akwaba as $i => $m) {
            User::updateOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $m['name'])) . '@itcj.org'],
                [
                    'name' => $m['name'],
                    'password' => Hash::make('password'),
                    'role' => 'membre',
                    'community_id' => $communities['Akwaba'] ?? null,
                    'room_number' => $m['room'],
                    'phone' => '+237 6 ' . rand(70, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . str_pad($i + 30, 2, '0', STR_PAD_LEFT),
                    'is_active' => true,
                    'title_prefix' => $m['prefix'],
                    'birthday' => $m['birthday'],
                ]
            );
        }

        // Utilisateur communication
        User::updateOrCreate(
            ['email' => 'communication@itcj.org'],
            [
                'name' => 'Service Communication',
                'password' => Hash::make('password'),
                'role' => 'communication',
                'community_id' => $communities['Mveng'] ?? null,
                'room_number' => 'B01',
                'phone' => '+237 6 99 00 00 02',
                'is_active' => true,
                'title_prefix' => null,
                'birthday' => null,
            ]
        );

        // Utilisateur sante
        User::updateOrCreate(
            ['email' => 'sante@itcj.org'],
            [
                'name' => 'Dr. Marie ATANGANA',
                'password' => Hash::make('password'),
                'role' => 'sante',
                'community_id' => $communities['Tansi'] ?? null,
                'room_number' => 'B02',
                'phone' => '+237 6 99 00 00 03',
                'is_active' => true,
                'title_prefix' => 'Dr.',
                'birthday' => '1980-06-12',
            ]
        );

        // Formateurs
        $formateurs = [
            ['name' => 'Jacques ESSONO', 'prefix' => 'P.', 'birthday' => '1970-02-14', 'community' => 'Favre'],
            ['name' => 'Andre MVONDO', 'prefix' => 'P.', 'birthday' => '1972-10-08', 'community' => 'Favre'],
        ];

        foreach ($formateurs as $i => $f) {
            User::updateOrCreate(
                ['email' => 'formateur' . ($i + 1) . '@itcj.org'],
                [
                    'name' => $f['name'],
                    'password' => Hash::make('password'),
                    'role' => 'formateur',
                    'community_id' => $communities[$f['community']] ?? null,
                    'room_number' => 'F0' . ($i + 1),
                    'phone' => '+237 6 99 00 00 0' . ($i + 4),
                    'is_active' => true,
                    'title_prefix' => $f['prefix'],
                    'birthday' => $f['birthday'],
                ]
            );
        }

        $this->command->info('');
        $this->command->info('=== Utilisateurs crees ===');
        $this->command->info('Admin: recteur@itcj.org / password');
        $this->command->info('Communication: communication@itcj.org / password');
        $this->command->info('Sante: sante@itcj.org / password');
        $this->command->info('Membres: [prenom].[nom]@itcj.org / password');
        $this->command->info('');
    }
}
