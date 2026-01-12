<?php

namespace Database\Seeders;

use App\Models\MealInscription;
use App\Models\SundayMeal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SundayMealSeeder extends Seeder
{
    public function run(): void
    {
        // Get next 4 Sundays
        $nextSunday = Carbon::now()->next(Carbon::SUNDAY);

        for ($i = 0; $i < 4; $i++) {
            $mealDate = $nextSunday->copy()->addWeeks($i);

            $meal = SundayMeal::create([
                'meal_date' => $mealDate,
                'max_capacity' => 60,
                'is_open' => $i < 2, // First 2 are open, others are closed
                'notes' => $i === 0 ? 'Repas special du dimanche - Menu africain' : null,
            ]);

            // Add some inscriptions for the first meal
            if ($i === 0) {
                $users = User::where('role', '!=', 'admin')->take(10)->get();
                foreach ($users as $index => $user) {
                    MealInscription::create([
                        'sunday_meal_id' => $meal->id,
                        'user_id' => $user->id,
                        'guests_count' => $index % 3, // 0, 1, or 2 guests
                        'guest_names' => $index % 3 > 0 ? array_fill(0, $index % 3, 'Invite ' . ($index + 1)) : null,
                        'registered_at' => now()->subDays(rand(1, 5)),
                    ]);
                }
            }
        }
    }
}
