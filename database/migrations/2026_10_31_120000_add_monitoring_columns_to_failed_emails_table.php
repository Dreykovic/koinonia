<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colonnes de corrélation et de relance des emails échoués (issue #28) :
     * request_id relie l'entrée aux logs, payload (chiffré) permet le renvoi.
     */
    public function up(): void
    {
        Schema::table('failed_emails', function (Blueprint $table) {
            $table->string('source', 16)->nullable()->after('related_model_id');
            $table->string('request_id', 64)->nullable()->after('source');
            $table->unsignedInteger('attempts')->default(1)->after('request_id');
            $table->timestamp('last_attempt_at')->nullable()->after('attempts');
            $table->longText('payload')->nullable()->after('last_attempt_at');

            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::table('failed_emails', function (Blueprint $table) {
            $table->dropIndex(['request_id']);
            $table->dropColumn(['source', 'request_id', 'attempts', 'last_attempt_at', 'payload']);
        });
    }
};
