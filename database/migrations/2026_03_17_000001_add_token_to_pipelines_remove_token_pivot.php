<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add token column to import_pipelines
        Schema::table('import_pipelines', function (Blueprint $table) {
            $table->string('token')->nullable()->after('description');
        });

        // 2. Migrate existing assignments: copy the token value from organization_tokens
        //    to each pipeline that had a token assigned via the pivot table.
        //    Each pipeline gets the token of its first assigned organization token.
        if (Schema::hasTable('organization_token_pipeline')) {
            $assignments = DB::table('organization_token_pipeline as otp')
                ->join('organization_tokens as ot', 'ot.id', '=', 'otp.organization_token_id')
                ->select('otp.pipeline_id', 'ot.token')
                ->orderBy('otp.pipeline_id')
                ->orderBy('otp.id')
                ->get()
                ->unique('pipeline_id'); // keep only the first token per pipeline

            foreach ($assignments as $row) {
                DB::table('import_pipelines')
                    ->where('id', $row->pipeline_id)
                    ->whereNull('token')
                    ->update(['token' => $row->token]);
            }

            // 3. Drop the now-redundant pivot table
            Schema::dropIfExists('organization_token_pipeline');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the pivot table
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

        Schema::table('import_pipelines', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
};
