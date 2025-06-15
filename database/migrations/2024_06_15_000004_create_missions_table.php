<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['checkin', 'checkout']);
            $table->dateTime('scheduled_at');
            $table->string('address');
            $table->string('tenant_name');
            $table->string('tenant_phone')->nullable();
            $table->string('tenant_email')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('agent_id')->nullable()->constrained('users');
            $table->enum('status', ['unassigned', 'assigned', 'in_progress', 'completed', 'cancelled'])
                  ->default('unassigned');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('missions');
    }
};