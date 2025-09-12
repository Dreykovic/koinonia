<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulletin_id')->nullable()->constrained('bulletin_editions')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('activity_date')->nullable();
            $table->time('activity_time')->nullable();
            $table->string('location')->nullable();
            $table->enum('type', ['messe', 'reunion', 'visite', 'evenement', 'autre'])->default('autre');
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // daily, weekly, etc.
            $table->boolean('is_highlighted')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('activity_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_activities');
    }
};
