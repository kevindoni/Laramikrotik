<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ppp_profiles', function (Blueprint $table) {
            $table->integer('billing_cycle_day')->nullable()->after('price')->comment('Day of month for billing cycle (1-31)');
            $table->enum('billing_period', ['monthly', 'quarterly', 'annually'])->default('monthly')->after('billing_cycle_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ppp_profiles', function (Blueprint $table) {
            $table->dropColumn(['billing_cycle_day', 'billing_period']);
        });
    }
};
