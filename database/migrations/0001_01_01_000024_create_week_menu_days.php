<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('week_menu_days', function (Blueprint $table) {
            $table->tinyInteger('id')->primary();
            $table->string('code', 10)->unique()->comment('lundi, mardi…');   // lundi, mardi…
            $table->tinyInteger('position')->comment('ordre affichage');
        });


        DB::table('week_menu_days')->insert([
            ['id'=>1,'code'=>'lundi','position'=>1],
            ['id'=>2,'code'=>'mardi','position'=>2],
            ['id'=>3,'code'=>'mercredi','position'=>3],
            ['id'=>4,'code'=>'jeudi','position'=>4],
            ['id'=>5,'code'=>'vendredi','position'=>5],
            ['id'=>6,'code'=>'samedi','position'=>6],
            ['id'=>7,'code'=>'dimanche','position'=>7],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('week_menu_days');
    }
};
