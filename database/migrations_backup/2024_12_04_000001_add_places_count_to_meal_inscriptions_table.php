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
        Schema::table('meal_inscriptions', function (Blueprint $table) {
            $table->integer('places_count')->default(1)->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meal_inscriptions', function (Blueprint $table) {
            $table->dropColumn('places_count');
        });
    }
};
