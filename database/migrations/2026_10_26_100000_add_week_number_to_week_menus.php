<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cycle de menus sur 4 semaines.
 * Migration additive : les menus existants deviennent la semaine 1 et sont
 * dupliques vers les semaines 2 a 4. Aucune donnee n'est supprimee.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('week_menus', function (Blueprint $table) {
            $table->unsignedTinyInteger('week_number')->default(1)->after('id');
        });

        // Les cles etrangeres s'appuient sur l'ancien index unique : on cree
        // d'abord des index dedies et le nouvel unique, puis on retire l'ancien.
        Schema::table('week_menus', function (Blueprint $table) {
            $table->index('day_id', 'week_menus_day_id_index');
            $table->index('period_id', 'week_menus_period_id_index');
            $table->unique(['week_number', 'day_id', 'period_id'], 'week_menus_week_day_period_unique');
        });

        Schema::table('week_menus', function (Blueprint $table) {
            $table->dropUnique('week_menus_day_id_period_id_unique');
        });

        Schema::create('menu_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('active_week')->default(1);
            $table->timestamps();
        });

        DB::table('menu_settings')->insert([
            'active_week' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Duplique la semaine 1 vers les semaines 2, 3 et 4.
        $base = DB::table('week_menus')->where('week_number', 1)->get();

        foreach ([2, 3, 4] as $week) {
            $rows = $base->map(fn ($m) => [
                'week_number' => $week,
                'day_id' => $m->day_id,
                'period_id' => $m->period_id,
                'dish_name' => $m->dish_name,
                'description' => $m->description,
                'price' => $m->price,
                'available' => $m->available,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            DB::table('week_menus')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_settings');

        // Seule la semaine 1 (menus d'origine) est conservee.
        DB::table('week_menus')->where('week_number', '>', 1)->delete();

        Schema::table('week_menus', function (Blueprint $table) {
            $table->unique(['day_id', 'period_id'], 'week_menus_day_id_period_id_unique');
        });

        Schema::table('week_menus', function (Blueprint $table) {
            $table->dropUnique('week_menus_week_day_period_unique');
            $table->dropIndex('week_menus_day_id_index');
            $table->dropIndex('week_menus_period_id_index');
            $table->dropColumn('week_number');
        });
    }
};
