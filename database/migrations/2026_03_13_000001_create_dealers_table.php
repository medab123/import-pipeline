<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_uuid');
            $table->foreign('organization_uuid')->references('uuid')->on('organizations')->cascadeOnDelete();

            $table->string('name');
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->string('posting_address')->nullable();
            $table->string('website_url')->nullable();
            $table->decimal('fbmp_payment', 10, 2)->default(0);
            $table->text('fbmp_app_access_token')->nullable();
            $table->string('fbmp_app_url')->nullable();
            $table->string('payment_period')->default('month');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealers');
    }
};
