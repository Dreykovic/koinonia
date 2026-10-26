<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permet la suppression definitive d'un membre sans perdre les bulletins,
 * activites et reflexions qu'il a crees : created_by passe a NULL au lieu de
 * bloquer la suppression. Aucune donnee existante n'est modifiee.
 */
return new class extends Migration {
    private const TABLES = ['bulletin_editions', 'weekly_activities', 'spiritual_reflections'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            $this->rebuild($table, nullable: true);
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            // Impossible de remettre NOT NULL si des lignes ont perdu leur auteur.
            if (DB::table($table)->whereNull('created_by')->exists()) {
                throw new RuntimeException("Rollback impossible : {$table} contient des lignes sans auteur.");
            }
            $this->rebuild($table, nullable: false);
        }
    }

    private function rebuild(string $table, bool $nullable): void
    {
        // Le changement de colonne s'appuie sur la syntaxe MySQL/MariaDB (prod).
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table($table, fn (Blueprint $t) => $t->dropForeign(['created_by']));

        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `created_by` BIGINT UNSIGNED %s',
            $table,
            $nullable ? 'NULL' : 'NOT NULL'
        ));

        Schema::table($table, function (Blueprint $t) use ($nullable) {
            $fk = $t->foreign('created_by')->references('id')->on('users');
            if ($nullable) {
                $fk->nullOnDelete();
            }
        });
    }
};
