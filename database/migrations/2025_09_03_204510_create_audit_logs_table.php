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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // create, update, delete, view, login, logout, etc.
            $table->string('auditable_type')->nullable(); // Model class name
            $table->unsignedBigInteger('auditable_id')->nullable(); // Model ID
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_email')->nullable();
            $table->json('user_roles')->nullable();
            $table->string('action'); // Specific action performed
            $table->json('old_values')->nullable(); // Previous values for updates
            $table->json('new_values')->nullable(); // New values for creates/updates
            $table->json('metadata')->nullable(); // Additional context data
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->string('request_id')->nullable(); // For tracing requests
            $table->string('route_name')->nullable();
            $table->string('url')->nullable();
            $table->string('http_method', 10)->nullable();
            $table->integer('response_status')->nullable();
            $table->string('severity', 20)->default('info'); // info, warning, error, critical
            $table->boolean('is_sensitive')->default(false); // Mark sensitive operations
            $table->timestamp('occurred_at');
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'occurred_at']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['event_type', 'occurred_at']);
            $table->index(['is_sensitive', 'occurred_at']);
            $table->index(['severity', 'occurred_at']);
            $table->index('ip_address');
            $table->index('session_id');

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};