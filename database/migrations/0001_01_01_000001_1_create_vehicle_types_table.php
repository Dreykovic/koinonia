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
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
/**
 *            $table->enum('type', ['sante', 'formation', 'communautaire','autres']);
 */
        // Insert default settings

        DB::table('vehicle_types')->insert([
            ['id'=>1,'name'=>'sante'],
            ['id'=>2,'name'=>'formation'],
            ['id'=>3,'name'=>'communautaire'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};
