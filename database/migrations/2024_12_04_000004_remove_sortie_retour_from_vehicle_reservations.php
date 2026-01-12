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
        Schema::table('vehicle_reservations', function (Blueprint $table) {
            $table->dropColumn(['is_out', 'is_returned', 'actual_return_time', 'return_notes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_reservations', function (Blueprint $table) {
            $table->boolean('is_out')->default(false);
            $table->boolean('is_returned')->default(false);
            $table->time('actual_return_time')->nullable();
            $table->text('return_notes')->nullable();
        });
    }
};
