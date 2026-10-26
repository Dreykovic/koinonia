<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rotation automatique du cycle de menus (mode Auto / Manuel).
 * Desactivee par defaut : le comportement existant (choix manuel) continue.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('menu_settings', function (Blueprint $table) {
            $table->boolean('auto_rotation')->default(false)->after('active_week_id');
            $table->foreignId('anchor_week_id')
                ->nullable()
                ->after('auto_rotation')
                ->constrained('menu_weeks')
                ->nullOnDelete();
            $table->date('anchor_week_start')->nullable()->after('anchor_week_id');
        });
    }

    public function down(): void
    {
        Schema::table('menu_settings', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['anchor_week_id']);
            }
            $table->dropColumn(['anchor_week_id', 'anchor_week_start', 'auto_rotation']);
        });
    }
};
