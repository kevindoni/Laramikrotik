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
        // Add columns to ppp_profiles table
        Schema::table('ppp_profiles', function (Blueprint $table) {
            $table->string('mikrotik_id')->nullable()->after('is_active');
            $table->boolean('auto_sync')->default(true)->after('mikrotik_id');
        });

        // Add columns to ppp_secrets table
        Schema::table('ppp_secrets', function (Blueprint $table) {
            $table->boolean('auto_sync')->default(true)->after('mikrotik_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ppp_profiles', function (Blueprint $table) {
            $table->dropColumn(['mikrotik_id', 'auto_sync']);
        });

        Schema::table('ppp_secrets', function (Blueprint $table) {
            $table->dropColumn('auto_sync');
        });
    }
};
