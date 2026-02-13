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
        Schema::create('target_fields', function (Blueprint $table) {
            $table->id();
            // Use organization_uuid as FK since organizations use UUID PK
            $table->uuid('organization_uuid');
            $table->foreign('organization_uuid')->references('uuid')->on('organizations')->cascadeOnDelete();
            
            $table->string('field');
            $table->string('label');
            $table->string('category')->nullable();
            $table->string('description')->nullable();
            $table->string('type')->default('string'); 
            $table->string('model')->nullable(); 
            $table->timestamps();

            // Unique constraint per organization + field? Or just index.
            // Let's assume field names should be unique for an organization to avoid confusion.
            $table->unique(['organization_uuid', 'field']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_fields');
    }
};
