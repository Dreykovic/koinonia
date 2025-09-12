<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates a partial unique index to prevent overlapping reservations
     * for the same vehicle on the same date/time slot.
     */
    public function up(): void
    {
        // Check if using PostgreSQL or MySQL
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: partial index (only active reservations)
            DB::statement('
                CREATE UNIQUE INDEX unique_active_vehicle_slot
                ON vehicle_reservations(vehicle_id, reservation_date, start_time)
                WHERE status IN (\'pending\', \'approved\')
            ');
        } else {
            // MySQL: use a generated column approach or simple unique index
            // Note: MySQL doesn't support partial indexes, so we create a full index
            // The application logic handles cancelled/rejected status filtering
            DB::statement('
                CREATE UNIQUE INDEX unique_active_vehicle_slot
                ON vehicle_reservations(vehicle_id, reservation_date, start_time, status)
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS unique_active_vehicle_slot ON vehicle_reservations');
    }
};
