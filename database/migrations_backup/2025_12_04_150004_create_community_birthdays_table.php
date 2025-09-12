<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add birthday field to users table
        Schema::table('users', function (Blueprint $table) {
            $table->date('birthday')->nullable()->after('room_number');
            $table->string('title_prefix')->nullable()->after('name'); // P., S., Fr., etc.
        });

        // Create a separate table for tracking birthday wishes sent
        Schema::create('birthday_wishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->year('year');
            $table->boolean('wish_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('birthday_wishes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['birthday', 'title_prefix']);
        });
    }
};
