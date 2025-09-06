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
        Schema::table('checklists', function (Blueprint $table) {
            // First, ensure ops_validation_comments exists (it should from the other migration)
            if (!Schema::hasColumn('checklists', 'ops_validation_comments')) {
                $table->text('ops_validation_comments')->nullable();
            }
            
            // Then add the validation fields after ops_validation_comments
            if (!Schema::hasColumn('checklists', 'validated_by')) {
                $table->unsignedBigInteger('validated_by')->nullable()->after('ops_validation_comments');
            }
            if (!Schema::hasColumn('checklists', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }
            
            // Only add foreign key if the column was just created
            if (Schema::hasColumn('checklists', 'validated_by')) {
                try {
                    // Try to add the foreign key constraint
                    $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
                } catch (\Exception $e) {
                    // If it fails (e.g., constraint already exists), continue silently
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checklists', function (Blueprint $table) {
            if (Schema::hasColumn('checklists', 'validated_by')) {
                try {
                    $table->dropForeign(['validated_by']);
                } catch (\Exception $e) {
                    // If it fails (e.g., constraint doesn't exist), continue silently
                }
                $table->dropColumn(['validated_by', 'validated_at']);
            }
        });
    }
};
