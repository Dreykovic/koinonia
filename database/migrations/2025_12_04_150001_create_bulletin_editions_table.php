<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletin_editions', function (Blueprint $table) {
            $table->id();
            $table->integer('edition_number');
            $table->date('week_start');
            $table->date('week_end');
            $table->year('year');
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['edition_number', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin_editions');
    }
};
