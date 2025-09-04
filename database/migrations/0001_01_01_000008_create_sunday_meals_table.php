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
        Schema::create('sunday_meals', function (Blueprint $table) {
            $table->id();
            $table->date('meal_date')->unique();
            $table->integer('max_capacity')->default(60);
            $table->boolean('is_open')->default(true);
            $table->boolean('allow_multiple_places')->default(true);
            $table->unsignedTinyInteger('max_places_per_user')->default(5);
            $table->boolean('allow_guests')->default(true);
            $table->unsignedTinyInteger('max_guests_per_user')->default(5);
            $table->datetime('registration_deadline')->nullable();
            $table->datetime('cancellation_deadline')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sunday_meals');
    }
};
