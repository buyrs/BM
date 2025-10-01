<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to notifications table for performance
        if (Schema::hasTable('notifications')) {
            try {
                Schema::table('notifications', function (Blueprint $table) {
                    $table->index('user_id');
                    $table->index('type');
                    $table->index('read_at');
                    $table->index('created_at');
                    $table->index(['user_id', 'read_at']);
                    $table->index(['user_id', 'type']);
                    $table->index(['user_id', 'created_at']);
                    $table->index('requires_action');
                    $table->index('priority');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, continue
            }
        }

        // Add indexes to file_metadata table for performance
        if (Schema::hasTable('file_metadata')) {
            try {
                Schema::table('file_metadata', function (Blueprint $table) {
                    $table->index('property_id');
                    $table->index('mission_id');
                    $table->index('checklist_id');
                    $table->index('uploaded_by');
                    $table->index('mime_type');
                    $table->index('created_at');
                    $table->index(['property_id', 'created_at']);
                    $table->index(['mission_id', 'created_at']);
                    $table->index(['uploaded_by', 'created_at']);
                });
            } catch (\Exception $e) {
                // Indexes may already exist, continue
            }
        }

        // Add indexes to maintenance_requests table for performance
        if (Schema::hasTable('maintenance_requests')) {
            try {
                Schema::table('maintenance_requests', function (Blueprint $table) {
                    $table->index('mission_id');
                    $table->index('checklist_id');
                    $table->index('reported_by');
                    $table->index('assigned_to');
                    $table->index('status');
                    $table->index('priority');
                    $table->index('created_at');
                    $table->index(['status', 'priority']);
                    $table->index(['assigned_to', 'status']);
                    $table->index(['mission_id', 'status']);
                });
            } catch (\Exception $e) {
                // Indexes may already exist, continue
            }
        }

        // Add indexes to email_delivery_statuses table for performance
        if (Schema::hasTable('email_delivery_statuses')) {
            try {
                Schema::table('email_delivery_statuses', function (Blueprint $table) {
                    $table->index('status');
                    $table->index('sent_at');
                    $table->index('delivered_at');
                    $table->index('failed_at');
                    $table->index(['status', 'sent_at']);
                    $table->index('recipient_email');
                });
            } catch (\Exception $e) {
                // Indexes may already exist, continue
            }
        }

        // Add indexes to personal_access_tokens table for API performance
        if (Schema::hasTable('personal_access_tokens')) {
            try {
                Schema::table('personal_access_tokens', function (Blueprint $table) {
                    $table->index('tokenable_id');
                    $table->index('tokenable_type');
                    $table->index('last_used_at');
                    $table->index('expires_at');
                    $table->index(['tokenable_type', 'tokenable_id']);
                });
            } catch (\Exception $e) {
                // Indexes may already exist, continue
            }
        }

        // Add additional indexes to existing tables for better query performance
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->index('email_verified_at');
                $table->index('last_login_at');
                $table->index('two_factor_enabled');
                $table->index(['role', 'email_verified_at']);
            });
        } catch (\Exception $e) {
            // Indexes may already exist, continue
        }

        try {
            Schema::table('missions', function (Blueprint $table) {
                $table->index('checkin_date');
                $table->index('checkout_date');
                $table->index(['status', 'checkin_date']);
                $table->index(['assigned_to', 'status', 'checkin_date']);
            });
        } catch (\Exception $e) {
            // Indexes may already exist, continue
        }

        // Add full-text search indexes where supported
        if (config('database.default') === 'mysql') {
            try {
                DB::statement('ALTER TABLE properties ADD FULLTEXT(property_address, description)');
            } catch (\Exception $e) {
                // Index may already exist, continue
            }
            
            try {
                DB::statement('ALTER TABLE missions ADD FULLTEXT(title, description)');
            } catch (\Exception $e) {
                // Index may already exist, continue
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from notifications table
        if (Schema::hasTable('notifications')) {
            try {
                Schema::table('notifications', function (Blueprint $table) {
                    $table->dropIndex(['user_id']);
                    $table->dropIndex(['type']);
                    $table->dropIndex(['read_at']);
                    $table->dropIndex(['created_at']);
                    $table->dropIndex(['user_id', 'read_at']);
                    $table->dropIndex(['user_id', 'type']);
                    $table->dropIndex(['user_id', 'created_at']);
                    $table->dropIndex(['requires_action']);
                    $table->dropIndex(['priority']);
                });
            } catch (\Exception $e) {
                // Indexes may not exist, continue
            }
        }

        // Remove indexes from file_metadata table
        if (Schema::hasTable('file_metadata')) {
            try {
                Schema::table('file_metadata', function (Blueprint $table) {
                    $table->dropIndex(['property_id']);
                    $table->dropIndex(['mission_id']);
                    $table->dropIndex(['checklist_id']);
                    $table->dropIndex(['uploaded_by']);
                    $table->dropIndex(['mime_type']);
                    $table->dropIndex(['created_at']);
                    $table->dropIndex(['property_id', 'created_at']);
                    $table->dropIndex(['mission_id', 'created_at']);
                    $table->dropIndex(['uploaded_by', 'created_at']);
                });
            } catch (\Exception $e) {
                // Indexes may not exist, continue
            }
        }

        // Remove indexes from maintenance_requests table
        if (Schema::hasTable('maintenance_requests')) {
            try {
                Schema::table('maintenance_requests', function (Blueprint $table) {
                    $table->dropIndex(['mission_id']);
                    $table->dropIndex(['checklist_id']);
                    $table->dropIndex(['reported_by']);
                    $table->dropIndex(['assigned_to']);
                    $table->dropIndex(['status']);
                    $table->dropIndex(['priority']);
                    $table->dropIndex(['created_at']);
                    $table->dropIndex(['status', 'priority']);
                    $table->dropIndex(['assigned_to', 'status']);
                    $table->dropIndex(['mission_id', 'status']);
                });
            } catch (\Exception $e) {
                // Indexes may not exist, continue
            }
        }

        // Remove indexes from email_delivery_statuses table
        if (Schema::hasTable('email_delivery_statuses')) {
            try {
                Schema::table('email_delivery_statuses', function (Blueprint $table) {
                    $table->dropIndex(['status']);
                    $table->dropIndex(['sent_at']);
                    $table->dropIndex(['delivered_at']);
                    $table->dropIndex(['failed_at']);
                    $table->dropIndex(['status', 'sent_at']);
                    $table->dropIndex(['recipient_email']);
                });
            } catch (\Exception $e) {
                // Indexes may not exist, continue
            }
        }

        // Remove indexes from personal_access_tokens table
        if (Schema::hasTable('personal_access_tokens')) {
            try {
                Schema::table('personal_access_tokens', function (Blueprint $table) {
                    $table->dropIndex(['tokenable_id']);
                    $table->dropIndex(['tokenable_type']);
                    $table->dropIndex(['last_used_at']);
                    $table->dropIndex(['expires_at']);
                    $table->dropIndex(['tokenable_type', 'tokenable_id']);
                });
            } catch (\Exception $e) {
                // Indexes may not exist, continue
            }
        }

        // Remove additional indexes from existing tables
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['email_verified_at']);
                $table->dropIndex(['last_login_at']);
                $table->dropIndex(['two_factor_enabled']);
                $table->dropIndex(['role', 'email_verified_at']);
            });
        } catch (\Exception $e) {
            // Indexes may not exist, continue
        }

        try {
            Schema::table('missions', function (Blueprint $table) {
                $table->dropIndex(['checkin_date']);
                $table->dropIndex(['checkout_date']);
                $table->dropIndex(['status', 'checkin_date']);
                $table->dropIndex(['assigned_to', 'status', 'checkin_date']);
            });
        } catch (\Exception $e) {
            // Indexes may not exist, continue
        }

        // Remove full-text search indexes where supported
        if (config('database.default') === 'mysql') {
            try {
                DB::statement('ALTER TABLE properties DROP INDEX property_address');
            } catch (\Exception $e) {
                // Index may not exist, continue
            }
            
            try {
                DB::statement('ALTER TABLE missions DROP INDEX title');
            } catch (\Exception $e) {
                // Index may not exist, continue
            }
        }
    }
};