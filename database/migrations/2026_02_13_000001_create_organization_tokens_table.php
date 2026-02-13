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
        Schema::create('organization_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_uuid');
            $table->foreign('organization_uuid')->references('uuid')->on('organizations')->cascadeOnDelete();
            
            $table->string('name');
            $table->string('token', 64)->unique(); // We'll store a hashed version
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_tokens');
    }
};
