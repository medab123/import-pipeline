<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_fbmp_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
            $table->uuid('organization_uuid');
            $table->foreign('organization_uuid')->references('uuid')->on('organizations')->cascadeOnDelete();
            $table->text('token');
            $table->string('user_email')->nullable();
            $table->unsignedInteger('limit_account')->default(999);
            $table->timestamps();

            $table->index(['dealer_id', 'id']);
        });

        // Migrate existing single tokens into the new table, preserving the
        // original creation timestamp so the "first token" ordering stays stable.
        DB::table('dealers')
            ->whereNotNull('fbmp_app_access_token')
            ->where('fbmp_app_access_token', '!=', '')
            ->orderBy('id')
            ->select(['id', 'organization_uuid', 'fbmp_app_access_token', 'created_at'])
            ->each(function (object $dealer): void {
                DB::table('dealer_fbmp_tokens')->insert([
                    'dealer_id' => $dealer->id,
                    'organization_uuid' => $dealer->organization_uuid,
                    'token' => $dealer->fbmp_app_access_token,
                    'user_email' => null,
                    'limit_account' => 999,
                    'created_at' => $dealer->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('dealers', function (Blueprint $table) {
            $table->dropColumn('fbmp_app_access_token');
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->text('fbmp_app_access_token')->nullable()->after('website_urls');
        });

        // Restore the first (oldest) token of each dealer into the legacy column.
        DB::table('dealer_fbmp_tokens')
            ->orderBy('dealer_id')
            ->orderBy('id')
            ->get(['dealer_id', 'token'])
            ->groupBy('dealer_id')
            ->each(function ($tokens, int $dealerId): void {
                DB::table('dealers')
                    ->where('id', $dealerId)
                    ->update(['fbmp_app_access_token' => $tokens->first()->token]);
            });

        Schema::dropIfExists('dealer_fbmp_tokens');
    }
};
