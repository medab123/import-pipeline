<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Move tokens from import_pipelines to organizations.
     * Each organization gets one token (taken from its first pipeline that has one).
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('token')->nullable()->unique()->after('slug');
        });

        // Migrate: copy the token from the first pipeline of each org to the org itself.
        $orgTokens = DB::table('import_pipelines')
            ->whereNotNull('token')
            ->select('organization_uuid', 'token')
            ->orderBy('id')
            ->get()
            ->unique('organization_uuid');

        foreach ($orgTokens as $row) {
            DB::table('organizations')
                ->where('uuid', $row->organization_uuid)
                ->whereNull('token')
                ->update(['token' => $row->token]);
        }
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
};
