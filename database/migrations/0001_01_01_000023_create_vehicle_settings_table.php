<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_settings', function (Blueprint $table) {
            $table->id();
            $table->time('opening_hour')->default('07:00:00');
            $table->time('closing_hour')->default('20:00:00');
            $table->unsignedInteger('max_future_days')->default(14);
            $table->timestamps();
        });

        // Insert default settings
        DB::table('vehicle_settings')->insert([
            'opening_hour' => '07:00:00',
            'closing_hour' => '20:00:00',
            'max_future_days' => 14,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_settings');
    }
};
