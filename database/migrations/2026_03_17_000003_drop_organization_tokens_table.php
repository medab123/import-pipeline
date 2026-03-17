<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tokens are now stored directly on import_pipelines.token.
     * The organization_tokens table and its related infrastructure are no longer needed.
     */
    public function up(): void
    {
        Schema::dropIfExists('organization_tokens');
    }

    public function down(): void
    {
        Schema::create('organization_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('organization_uuid');
            $table->string('name');
            $table->string('token');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('organization_uuid');
            $table->index('token');
        });
    }
};
