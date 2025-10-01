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
        Schema::create('email_delivery_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->string('to_email');
            $table->string('subject');
            $table->enum('status', ['queued', 'sending', 'sent', 'delivered', 'failed', 'retrying']);
            $table->string('provider');
            $table->integer('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['to_email', 'created_at']);
            $table->index('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_delivery_statuses');
    }
};