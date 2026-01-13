<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Creer la table communities
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Inserer les communautes par defaut
        $communities = ['Matungulu', 'Mveng', 'Xavier', 'Loyola', 'Akwaba', 'Tansi', 'Favre'];
        foreach ($communities as $name) {
            DB::table('communities')->insert([
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Ajouter la colonne community_id aux users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('community_id')->nullable()->after('role')->constrained('communities')->nullOnDelete();
        });

        // Migrer les donnees existantes des users
        $communityMap = DB::table('communities')->pluck('id', 'name');
        $users = DB::table('users')->whereNotNull('community')->get();
        foreach ($users as $user) {
            if (isset($communityMap[$user->community])) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['community_id' => $communityMap[$user->community]]);
            }
        }

        // Ajouter la colonne community_id aux community_requests
        Schema::table('community_requests', function (Blueprint $table) {
            $table->foreignId('community_id')->nullable()->after('email')->constrained('communities')->nullOnDelete();
        });

        // Migrer les donnees existantes des community_requests
        $requests = DB::table('community_requests')->whereNotNull('community')->get();
        foreach ($requests as $request) {
            if (isset($communityMap[$request->community])) {
                DB::table('community_requests')
                    ->where('id', $request->id)
                    ->update(['community_id' => $communityMap[$request->community]]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('community_requests', function (Blueprint $table) {
            $table->dropForeign(['community_id']);
            $table->dropColumn('community_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['community_id']);
            $table->dropColumn('community_id');
        });

        Schema::dropIfExists('communities');
    }
};
