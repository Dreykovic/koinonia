<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sunday_meals', function (Blueprint $table) {
            $table->boolean('allow_multiple_places')->default(true)->after('is_open');
            $table->unsignedTinyInteger('max_places_per_user')->default(5)->after('allow_multiple_places');
            $table->boolean('allow_guests')->default(true)->after('max_places_per_user');
            $table->unsignedTinyInteger('max_guests_per_user')->default(5)->after('allow_guests');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sunday_meals', function (Blueprint $table) {
            $table->dropColumn([
                'allow_multiple_places',
                'max_places_per_user',
                'allow_guests',
                'max_guests_per_user',
            ]);
        });
    }
};
