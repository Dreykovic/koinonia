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
        Schema::create('repair_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });


        $locations = [
            'Chambre',
            'Cuisine/refectoire',
            'Salle Tele',
            'Toilettes',
            'Chapelle',
            'Salle informatique',
            'Parloir',
            'autre'
        ];

        foreach ($locations as $name) {
              DB::table('repair_locations')->insert(['name' => $name]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_locations');
    }
};
