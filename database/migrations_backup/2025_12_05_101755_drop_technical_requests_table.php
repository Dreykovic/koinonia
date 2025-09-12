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
        Schema::dropIfExists('technical_requests');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('technical_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('location')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['submitted', 'in_review', 'approved', 'in_progress', 'completed', 'rejected'])->default('submitted');
            $table->text('admin_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }
};
