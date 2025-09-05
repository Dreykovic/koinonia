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
        Schema::create('user_profile_vehicle_types', function (Blueprint $table) {
            $table->id();
           $table->foreignId('user_profile_id')->constrained('user_profiles')->onDelete('cascade');
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->unique(['user_profile_id','vehicle_type_id'],'profile_vehicle_unique');
            $table->timestamps();
        });

        // Insert default settings

        DB::table('user_profile_vehicle_types')->insert([
            // profile Admin vehicle types  Access
            ['user_profile_id'=>1,'vehicle_type_id'=>1],
            ['user_profile_id'=>1,'vehicle_type_id'=>2],
            ['user_profile_id'=>1,'vehicle_type_id'=>3],
            // profile Sante vehicle types  Access
            ['user_profile_id'=>2,'vehicle_type_id'=>1],
            // profile Etudiant vehicle types  Access
            ['user_profile_id'=>3,'vehicle_type_id'=>3],
            // profile Formateur vehicle types  Access
            ['user_profile_id'=>4,'vehicle_type_id'=>2],
            // profile Formateur vehicle types  Access
            ['user_profile_id'=>5,'vehicle_type_id'=>1],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profile_vehicle_types');
    }
};
