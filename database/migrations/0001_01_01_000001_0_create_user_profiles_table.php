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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default settings

        DB::table('user_profiles')->insert([
            ['id'=>1,'name'=>'administrateur'],
            ['id'=>2,'name'=>'sante'],
            ['id'=>3,'name'=>'etudiant'],
            ['id'=>4,'name'=>'formateur'],
            ['id'=>5,'name'=>'chauffeur'],
   
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
