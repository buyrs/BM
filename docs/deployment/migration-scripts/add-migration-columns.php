<?php

/**
 * Migration pour ajouter les colonnes nécessaires à la migration des données
 * 
 * Cette migration ajoute des colonnes temporaires pour tracer la migration
 * des missions existantes vers le système Bail Mobilité.
 */

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
        // Ajouter des colonnes de traçabilité à la table missions
        Schema::table('missions', function (Blueprint $table) {
            // Référence vers le BM créé lors de la migration
            $table->unsignedBigInteger('migrated_to_bm_id')->nullable()->after('updated_at');
            $table->timestamp('migration_date')->nullable()->after('migrated_to_bm_id');
            
            // Index pour les requêtes de migration
            $table->index('migrated_to_bm_id');
            
            // Contrainte de clé étrangère
            $table->foreign('migrated_to_bm_id')
                  ->references('id')
                  ->on('bail_mobilites')
                  ->onDelete('set null');
        });

        // Ajouter une table de log de migration
        Schema::create('migration_logs', function (Blueprint $table) {
            $table->id();
            $table->string('migration_type'); // 'missions_to_bm', 'data_cleanup', etc.
            $table->string('status'); // 'started', 'completed', 'failed'
            $table->json('parameters')->nullable(); // Paramètres de la migration
            $table->json('statistics')->nullable(); // Statistiques de la migration
            $table->text('error_message')->nullable(); // Message d'erreur si échec
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['migration_type', 'status']);
            $table->index('started_at');
        });

        // Ajouter une table de sauvegarde des données critiques
        Schema::create('migration_backup', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->unsignedBigInteger('record_id');
            $table->json('original_data'); // Données originales avant migration
            $table->json('migrated_data')->nullable(); // Données après migration
            $table->timestamp('backup_date');
            $table->timestamps();
            
            $table->index(['table_name', 'record_id']);
            $table->index('backup_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les tables de migration
        Schema::dropIfExists('migration_backup');
        Schema::dropIfExists('migration_logs');
        
        // Supprimer les colonnes ajoutées à missions
        Schema::table('missions', function (Blueprint $table) {
            $table->dropForeign(['migrated_to_bm_id']);
            $table->dropIndex(['migrated_to_bm_id']);
            $table->dropColumn(['migrated_to_bm_id', 'migration_date']);
        });
    }
};