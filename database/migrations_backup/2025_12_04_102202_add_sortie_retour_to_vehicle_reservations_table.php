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
            $table->boolean('is_out')->default(false)->after('notes');
            $table->boolean('is_returned')->default(false)->after('is_out');
            $table->time('actual_return_time')->nullable()->after('is_returned');
            $table->text('return_notes')->nullable()->after('actual_return_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_reservations', function (Blueprint $table) {
            $table->dropColumn(['is_out', 'is_returned', 'actual_return_time', 'return_notes']);
        });
    }
};
