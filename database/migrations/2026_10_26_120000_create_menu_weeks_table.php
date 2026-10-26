<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les semaines du cycle deviennent des entites a part entiere (nom, position,
 * presence dans le cycle). Migration additive : chaque numero de semaine
 * existant devient une ligne de menu_weeks et les plats y sont rattaches.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('menu_weeks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->unsignedSmallInteger('position')->default(1);
            $table->boolean('in_cycle')->default(true);
            $table->timestamps();
        });

        // Une semaine par numero deja utilise, dans l'ordre actuel du cycle.
        $numbers = DB::table('week_menus')
            ->distinct()
            ->orderBy('week_number')
            ->pluck('week_number');

        if ($numbers->isEmpty()) {
            $numbers = collect([1]);
        }

        $weekIds = [];
        foreach ($numbers->values() as $index => $number) {
            $weekIds[$number] = DB::table('menu_weeks')->insertGetId([
                'name' => 'Semaine ' . $number,
                'position' => $index + 1,
                'in_cycle' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('week_menus', function (Blueprint $table) {
            $table->foreignId('week_id')
                ->nullable()
                ->after('id')
                ->constrained('menu_weeks')
                ->cascadeOnDelete();
        });

        foreach ($weekIds as $number => $id) {
            DB::table('week_menus')->where('week_number', $number)->update(['week_id' => $id]);
        }

        Schema::table('week_menus', function (Blueprint $table) {
            $table->unique(['week_id', 'day_id', 'period_id'], 'week_menus_week_id_day_period_unique');
        });

        Schema::table('week_menus', function (Blueprint $table) {
            $table->dropUnique('week_menus_week_day_period_unique');
        });

        Schema::table('week_menus', function (Blueprint $table) {
            $table->dropColumn('week_number');
        });

        // La semaine en ligne pointe desormais vers une entite.
        Schema::table('menu_settings', function (Blueprint $table) {
            $table->foreignId('active_week_id')
                ->nullable()
                ->after('id')
                ->constrained('menu_weeks')
                ->nullOnDelete();
        });

        $activeNumber = (int) (DB::table('menu_settings')->value('active_week') ?? 1);
        $activeId = $weekIds[$activeNumber] ?? reset($weekIds);

        if (DB::table('menu_settings')->exists()) {
            DB::table('menu_settings')->update(['active_week_id' => $activeId]);
        } else {
            DB::table('menu_settings')->insert([
                'active_week_id' => $activeId,
                'active_week' => $activeNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('menu_settings', function (Blueprint $table) {
            $table->dropColumn('active_week');
        });
    }

    public function down(): void
    {
        Schema::table('menu_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('active_week')->default(1)->after('id');
        });

        $positions = DB::table('menu_weeks')->orderBy('position')->orderBy('id')->pluck('id')
            ->values()
            ->mapWithKeys(fn ($id, $index) => [$id => $index + 1]);

        $activeId = DB::table('menu_settings')->value('active_week_id');
        DB::table('menu_settings')->update(['active_week' => $positions[$activeId] ?? 1]);

        Schema::table('menu_settings', function (Blueprint $table) {
            // SQLite ne sait pas retirer une cle etrangere ; il n'en a pas cree.
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['active_week_id']);
            }
            $table->dropColumn('active_week_id');
        });

        Schema::table('week_menus', function (Blueprint $table) {
            $table->unsignedTinyInteger('week_number')->default(1)->after('id');
        });

        foreach ($positions as $id => $position) {
            DB::table('week_menus')->where('week_id', $id)->update(['week_number' => $position]);
        }

        Schema::table('week_menus', function (Blueprint $table) {
            $table->unique(['week_number', 'day_id', 'period_id'], 'week_menus_week_day_period_unique');
        });

        Schema::table('week_menus', function (Blueprint $table) {
            $table->dropUnique('week_menus_week_id_day_period_unique');
        });

        Schema::table('week_menus', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['week_id']);
            }
            $table->dropColumn('week_id');
        });

        Schema::dropIfExists('menu_weeks');
    }
};
