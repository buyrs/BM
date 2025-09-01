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
        Schema::table('bail_mobilite_signatures', function (Blueprint $table) {
            $table->json('signature_metadata')->nullable()->after('contract_pdf_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bail_mobilite_signatures', function (Blueprint $table) {
            $table->dropColumn('signature_metadata');
        });
    }
};
