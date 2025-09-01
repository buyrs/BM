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
        Schema::create('bail_mobilite_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bail_mobilite_id')->constrained('bail_mobilites')->onDelete('cascade');
            $table->enum('signature_type', ['entry', 'exit']);
            $table->foreignId('contract_template_id')->constrained('contract_templates');
            $table->text('tenant_signature')->nullable(); // Signature électronique du locataire
            $table->timestamp('tenant_signed_at')->nullable();
            $table->string('contract_pdf_path')->nullable(); // Chemin vers le contrat généré
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bail_mobilite_signatures');
    }
};
