<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The tables to add organization_uuid to.
     */
    private array $tables = [
        'import_pipelines',
        'import_pipeline_configs',
        'import_pipeline_executions',
        'import_pipeline_logs',
        'import_pipeline_templates',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->uuid('organization_uuid')->nullable()->index()->after('id');

                $table->foreign('organization_uuid')
                    ->references('uuid')
                    ->on('organizations')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['organization_uuid']);
                $table->dropColumn('organization_uuid');
            });
        }
    }
};
