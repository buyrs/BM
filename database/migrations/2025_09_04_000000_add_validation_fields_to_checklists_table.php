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
            if (!Schema::hasColumn('checklists', 'validated_by')) {
                $table->unsignedBigInteger('validated_by')->nullable()->after('ops_validation_comments');
            }
            if (!Schema::hasColumn('checklists', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }
            
            // Only add foreign key if the column was just created
            if (!Schema::hasColumn('checklists', 'validated_by')) {
                $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
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
                $table->dropForeign(['validated_by']);
                $table->dropColumn(['validated_by', 'validated_at']);
            }
        });
    }
};
