<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the organization_tokens table for multi-token-per-org support.
     * Migrates existing single tokens from organizations.token into this table.
     */
    public function up(): void
    {
        Schema::create('organization_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_uuid');
            $table->foreign('organization_uuid')->references('uuid')->on('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('token', 100)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('organization_uuid');
        });

        // Migrate existing single tokens from organizations.token
        if (Schema::hasColumn('organizations', 'token')) {
            $orgs = DB::table('organizations')
                ->whereNotNull('token')
                ->select('uuid', 'token', 'name')
                ->get();

            foreach ($orgs as $org) {
                DB::table('organization_tokens')->insert([
                    'organization_uuid' => $org->uuid,
                    'name' => 'Default Token',
                    'description' => 'Migrated from organization token for '.$org->name,
                    'token' => $org->token,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_tokens');
    }
};
