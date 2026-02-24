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
        Schema::create('spiritual_reflections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulletin_id')->nullable()->constrained('bulletin_editions')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->string('source')->nullable();
            $table->enum('type', ['spiritualite', 'theologie'])->default('spiritualite');
            $table->date('week_start')->nullable();
            $table->date('week_end')->nullable();
            $table->boolean('is_current')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['type', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spiritual_reflections');
    }
};

