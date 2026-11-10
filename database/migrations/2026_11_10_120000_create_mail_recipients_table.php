<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('email');
            $table->string('name')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_recipients');
    }
};
