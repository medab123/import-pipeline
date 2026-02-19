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
        Schema::create('organization_token_pipeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_token_id')
                ->constrained('organization_tokens')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('pipeline_id');
            $table->foreign('pipeline_id')
                ->references('id')
                ->on('import_pipelines')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_token_pipeline');
    }
};
