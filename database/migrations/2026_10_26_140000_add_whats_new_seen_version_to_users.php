<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Derniere version des nouveautes vue par l'utilisateur : l'encart
 * "Nouveautes" ne s'affiche qu'une fois par mise a jour.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whats_new_seen_version', 20)->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('whats_new_seen_version');
        });
    }
};
