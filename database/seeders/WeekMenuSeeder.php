<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WeekMenu;

class WeekMenuSeeder extends Seeder
{
    /**
     * Seed the weekly menu for all days (lunch & dinner).
     */
    public function run(): void
    {
        $menus = [
            // =====================
            // Monday
            // =====================
            ['day_id'=>1,'period_id'=>1,'dish_name'=>'Rice with tomato sauce','price'=>800,'available'=>true],
            ['day_id'=>1,'period_id'=>2,'dish_name'=>'Pasta with fish','price'=>900,'available'=>true],

            // =====================
            // Tuesday
            // =====================
            ['day_id'=>2,'period_id'=>1,'dish_name'=>'Beans with gari','price'=>700,'available'=>true],
            ['day_id'=>2,'period_id'=>2,'dish_name'=>'Pounded yam with sauce','price'=>1000,'available'=>true],

            // =====================
            // Wednesday
            // =====================
            ['day_id'=>3,'period_id'=>1,'dish_name'=>'Jollof rice','price'=>900,'available'=>true],
            ['day_id'=>3,'period_id'=>2,'dish_name'=>'Spaghetti bolognese','price'=>950,'available'=>true],

            // =====================
            // Thursday
            // =====================
            ['day_id'=>4,'period_id'=>1,'dish_name'=>'Fried rice','price'=>900,'available'=>true],
            ['day_id'=>4,'period_id'=>2,'dish_name'=>'Akume with okra sauce','price'=>800,'available'=>true],

            // =====================
            // Friday
            // =====================
            ['day_id'=>5,'period_id'=>1,'dish_name'=>'White rice and beans','price'=>800,'available'=>true],
            ['day_id'=>5,'period_id'=>2,'dish_name'=>'Grilled chicken with fries','price'=>1200,'available'=>true],

            // =====================
            // Saturday
            // =====================
            ['day_id'=>6,'period_id'=>1,'dish_name'=>'Fufu with light soup','price'=>1000,'available'=>true],
            ['day_id'=>6,'period_id'=>2,'dish_name'=>'Rice with peanut sauce','price'=>900,'available'=>true],

            // =====================
            // Sunday
            // =====================
            ['day_id'=>7,'period_id'=>1,'dish_name'=>'Special rice with meat','price'=>1200,'available'=>true],
            ['day_id'=>7,'period_id'=>2,'dish_name'=>'Chef special dinner','price'=>1500,'available'=>true],
        ];

        foreach ($menus as $menu) {
            WeekMenu::updateOrCreate(
                [
                    // One menu per day and period
                    'day_id' => $menu['day_id'],
                    'period_id' => $menu['period_id'],
                ],
                $menu
            );
        }
    }
}

