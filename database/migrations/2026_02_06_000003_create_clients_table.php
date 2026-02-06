<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active'); // active, inactive, suspended
            $table->json('settings')->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('user_id');
        });

        // Add client_id to properties table if not exists
        if (!Schema::hasColumn('properties', 'client_id')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->foreignId('client_id')->nullable()->after('id')->constrained()->nullOnDelete();
                $table->index('client_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('properties', 'client_id')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            });
        }

        Schema::dropIfExists('clients');
    }
};
