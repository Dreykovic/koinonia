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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('matricule')->nullable();
            $table->string('title_prefix')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->json('roles')->nullable();

            $table->foreignId('user_profile_id')->nullable()->constrained('user_profiles')->onDelete('cascade');
            $table->foreignId('community_id')->nullable()->constrained('communities')->nullOnDelete();
            $table->string('room_number')->nullable();
            $table->string('phone')->nullable();
            $table->date('birthday')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

