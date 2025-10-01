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
        Schema::table('users', function (Blueprint $table) {
            $table->string('two_factor_secret')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->json('two_factor_recovery_codes')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->integer('login_attempts')->default(0);
            $table->json('preferences')->nullable();
            $table->string('timezone')->default('UTC');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_enabled',
                'two_factor_confirmed_at',
                'two_factor_recovery_codes',
                'last_login_at',
                'login_attempts',
                'preferences',
                'timezone'
            ]);
        });
    }
};