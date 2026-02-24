<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('week_menu_periods', function (Blueprint $table) {
            $table->tinyInteger('id')->primary();
            $table->string('code', 10)->unique()->comment('midi, soir');
            $table->tinyInteger('position')->comment('ordre affichage');
        });
        
        // Insert default settings
        DB::table('week_menu_periods')->insert([
            ['id'=>1,'code'=>'midi','position'=>1],
            ['id'=>2,'code'=>'soir','position'=>2],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('week_menu_periods');
    }
};

