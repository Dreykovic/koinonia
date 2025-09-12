<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulletin_editions', function (Blueprint $table) {
            $table->string('title')->default('ATTOUNGBLAN')->after('id');
            $table->string('subtitle')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('bulletin_editions', function (Blueprint $table) {
            $table->dropColumn(['title', 'subtitle']);
        });
    }
};
