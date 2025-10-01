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
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->onDelete('cascade');
            $table->foreignId('checklist_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('checklist_item_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('reported_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->text('description');
            $table->enum('status', ['pending', 'approved', 'in_progress', 'completed', 'rejected'])->default('pending');
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'status']);
            $table->index(['mission_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
