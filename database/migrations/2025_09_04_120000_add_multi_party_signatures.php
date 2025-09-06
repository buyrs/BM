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
        // Add multi-party signature support to contract templates
        if (!Schema::hasColumn('contract_templates', 'signature_workflow')) {
            Schema::table('contract_templates', function (Blueprint $table) {
                $table->json('signature_workflow')->nullable()->after('admin_signature');
            });
        }
        
        if (!Schema::hasColumn('contract_templates', 'signature_parties')) {
            Schema::table('contract_templates', function (Blueprint $table) {
                $table->json('signature_parties')->nullable()->after('signature_workflow');
            });
        }
        
        if (!Schema::hasColumn('contract_templates', 'requires_multi_party')) {
            Schema::table('contract_templates', function (Blueprint $table) {
                $table->boolean('requires_multi_party')->default(false)->after('signature_parties');
            });
        }
        
        if (!Schema::hasColumn('contract_templates', 'signature_order')) {
            Schema::table('contract_templates', function (Blueprint $table) {
                $table->integer('signature_order')->default(0)->after('requires_multi_party');
            });
        }

        // Add multi-party signature support to bail mobilite signatures
        if (!Schema::hasColumn('bail_mobilite_signatures', 'additional_signatures')) {
            Schema::table('bail_mobilite_signatures', function (Blueprint $table) {
                $table->json('additional_signatures')->nullable()->after('signature_metadata');
            });
        }
        
        if (!Schema::hasColumn('bail_mobilite_signatures', 'signature_status')) {
            Schema::table('bail_mobilite_signatures', function (Blueprint $table) {
                $table->string('signature_status')->default('pending')->after('additional_signatures');
            });
        }
        
        if (!Schema::hasColumn('bail_mobilite_signatures', 'signature_workflow_history')) {
            Schema::table('bail_mobilite_signatures', function (Blueprint $table) {
                $table->json('signature_workflow_history')->nullable()->after('signature_status');
            });
        }
        
        if (!Schema::hasColumn('bail_mobilite_signatures', 'workflow_started_at')) {
            Schema::table('bail_mobilite_signatures', function (Blueprint $table) {
                $table->timestamp('workflow_started_at')->nullable()->after('signature_workflow_history');
            });
        }
        
        if (!Schema::hasColumn('bail_mobilite_signatures', 'workflow_completed_at')) {
            Schema::table('bail_mobilite_signatures', function (Blueprint $table) {
                $table->timestamp('workflow_completed_at')->nullable()->after('workflow_started_at');
            });
        }

        // Create table for signature parties (landlords, agents, etc.)
        if (!Schema::hasTable('signature_parties')) {
            Schema::create('signature_parties', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('phone')->nullable();
                $table->string('role'); // landlord, agent, witness, etc.
                $table->string('signature_method')->default('electronic'); // electronic, physical, digital
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['role', 'is_active']);
            });
        }

        // Create table for signature workflow steps
        if (!Schema::hasTable('signature_workflow_steps')) {
            Schema::create('signature_workflow_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_template_id')->constrained()->onDelete('cascade');
                $table->foreignId('signature_party_id')->constrained()->onDelete('cascade');
                $table->integer('order')->default(0);
                $table->boolean('is_required')->default(true);
                $table->integer('timeout_hours')->nullable(); // auto-expire after X hours
                $table->json('validation_rules')->nullable();
                $table->json('notification_settings')->nullable();
                $table->timestamps();

                $table->unique(['contract_template_id', 'signature_party_id'], 'sws_template_party_unique');
                $table->index(['contract_template_id', 'order'], 'sws_template_order_idx');
            });
        }

        // Create table for signature invitations
        if (!Schema::hasTable('signature_invitations')) {
            Schema::create('signature_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bail_mobilite_signature_id')->constrained()->onDelete('cascade');
                $table->foreignId('signature_party_id')->constrained()->onDelete('cascade');
                $table->string('token')->unique();
                $table->string('status')->default('pending'); // pending, sent, delivered, opened, completed, expired
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->json('delivery_metadata')->nullable();
                $table->json('signature_data')->nullable();
                $table->timestamps();

                $table->index(['token', 'status']);
                $table->index(['bail_mobilite_signature_id', 'signature_party_id'], 'si_signature_party_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_invitations');
        Schema::dropIfExists('signature_workflow_steps');
        Schema::dropIfExists('signature_parties');
        
        try {
            Schema::table('bail_mobilite_signatures', function (Blueprint $table) {
                $table->dropColumn([
                    'additional_signatures',
                    'signature_status',
                    'signature_workflow_history',
                    'workflow_started_at',
                    'workflow_completed_at'
                ]);
            });
        } catch (\Exception $e) {
            // Columns might not exist, continue with the rollback
            if (strpos($e->getMessage(), 'doesn\'t exist') === false && strpos($e->getMessage(), 'Unknown column') === false) {
                throw $e;
            }
        }

        try {
            Schema::table('contract_templates', function (Blueprint $table) {
                $table->dropColumn([
                    'signature_workflow',
                    'signature_parties',
                    'requires_multi_party',
                    'signature_order'
                ]);
            });
        } catch (\Exception $e) {
            // Columns might not exist, continue with the rollback
            if (strpos($e->getMessage(), 'doesn\'t exist') === false && strpos($e->getMessage(), 'Unknown column') === false) {
                throw $e;
            }
        }
    }
};