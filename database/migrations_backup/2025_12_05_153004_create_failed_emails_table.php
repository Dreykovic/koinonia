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
        Schema::create('failed_emails', function (Blueprint $table) {
            $table->id();
            $table->string('mailable_class');
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('subject')->nullable();
            $table->text('error_message');
            $table->string('related_model_type')->nullable();
            $table->unsignedBigInteger('related_model_id')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['is_resolved', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_emails');
    }
};
