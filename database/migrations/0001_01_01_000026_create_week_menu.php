<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('week_menus', function (Blueprint $table) {
            $table->id();

            $table->tinyInteger('day_id');
            $table->tinyInteger('period_id');

            $table->string('dish_name', 150);
            $table->text('description')->nullable();
            $table->integer('price')->nullable();
            $table->boolean('available')->default(true);

            $table->timestamps();

            $table->unique(['day_id', 'period_id']);

            $table->foreign('day_id')
                  ->references('id')->on('week_menu_days')
                  ->cascadeOnDelete();

            $table->foreign('period_id')
                  ->references('id')->on('week_menu_periods')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('week_menus');
    }
};

