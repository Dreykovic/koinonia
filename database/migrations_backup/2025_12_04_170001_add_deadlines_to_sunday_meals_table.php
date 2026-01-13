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
            $table->datetime('registration_deadline')->nullable()->after('is_open');
            $table->datetime('cancellation_deadline')->nullable()->after('registration_deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sunday_meals', function (Blueprint $table) {
            $table->dropColumn(['registration_deadline', 'cancellation_deadline']);
        });
    }
};
