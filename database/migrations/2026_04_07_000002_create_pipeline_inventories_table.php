<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_inventories', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('organization_uuid');
            $table->foreignId('pipeline_id')->constrained('import_pipelines')->cascadeOnDelete();
            $table->string('stock_number');
            $table->json('product_data');
            $table->timestamps();

            $table->unique(['pipeline_id', 'stock_number']);
            $table->index('organization_uuid');

            $table->foreign('organization_uuid')
                ->references('uuid')
                ->on('organizations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_inventories');
    }
};
