<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulletin_id')->nullable()->constrained('bulletin_editions')->onDelete('cascade');
            $table->date('week_start');
            $table->date('week_end');
            $table->enum('service_type', ['sante', 'tarife', 'chauffeur', 'intendance', 'sport', 'liturgie']);
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('assigned_name')->nullable(); // For non-user assignments
            $table->string('community')->nullable(); // For liturgie - which community
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['week_start', 'week_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_services');
    }
};
