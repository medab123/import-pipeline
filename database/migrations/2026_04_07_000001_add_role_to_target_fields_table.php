<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('target_fields', function (Blueprint $table) {
            $table->string('role')->nullable()->after('model');
            $table->index(['organization_uuid', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('target_fields', function (Blueprint $table) {
            $table->dropIndex(['organization_uuid', 'role']);
            $table->dropColumn('role');
        });
    }
};
