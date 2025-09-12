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
        Schema::create('community_requests', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->foreignId('community_id')->nullable()->constrained('communities')->nullOnDelete();
            $table->enum('request_type', ['reparation', 'achat']);

            // Champs pour les reparations
            $table->foreignId('repair_location_id')->nullable()->constrained('repair_locations')->nullOnDelete();
            $table->string('other_location')->nullable();
            $table->string('room_number')->nullable();
            $table->text('problem_description')->nullable();

            // Champs pour les achats
            $table->text('purchase_description')->nullable();

            // Gestion des demandes
            $table->enum('status', ['pending', 'in_progress', 'completed', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_requests');
    }
};
