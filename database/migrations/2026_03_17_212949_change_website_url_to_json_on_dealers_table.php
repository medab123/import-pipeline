<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->jsonb('website_urls')->nullable()->after('posting_address');
        });

        // Migrate existing single URL to JSON array
        DB::table('dealers')->whereNotNull('website_url')->each(function ($dealer) {
            DB::table('dealers')->where('id', $dealer->id)->update([
                'website_urls' => json_encode([$dealer->website_url]),
            ]);
        });

        Schema::table('dealers', function (Blueprint $table) {
            $table->dropColumn('website_url');
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->string('website_url')->nullable()->after('posting_address');
        });

        DB::table('dealers')->whereNotNull('website_urls')->each(function ($dealer) {
            $urls = json_decode($dealer->website_urls, true);
            DB::table('dealers')->where('id', $dealer->id)->update([
                'website_url' => $urls[0] ?? null,
            ]);
        });

        Schema::table('dealers', function (Blueprint $table) {
            $table->dropColumn('website_urls');
        });
    }
};
