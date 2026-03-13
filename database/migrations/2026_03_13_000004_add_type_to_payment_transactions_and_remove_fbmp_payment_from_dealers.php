<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('type')->default('dealer_payment')->after('dealer_id');
        });

        Schema::table('dealers', function (Blueprint $table) {
            $table->dropColumn('fbmp_payment');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('dealers', function (Blueprint $table) {
            $table->decimal('fbmp_payment', 10, 2)->default(0)->after('website_url');
        });
    }
};
