<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->onDelete('cascade');
            $table->json('general_info')->nullable();
            $table->json('rooms')->nullable();
            $table->json('utilities')->nullable();
            $table->string('tenant_signature')->nullable();
            $table->string('agent_signature')->nullable();
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->timestamps();
        });

        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained()->onDelete('cascade');
            $table->string('category');
            $table->string('item_name');
            $table->enum('condition', ['perfect', 'good', 'damaged', 'broken'])->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('checklist_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_item_id')->constrained()->onDelete('cascade');
            $table->string('photo_path');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('checklist_photos');
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('checklists');
    }
};