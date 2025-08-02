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
        Schema::table('mikrotik_settings', function (Blueprint $table) {
            $table->timestamp('last_disconnected_at')->nullable()->after('last_connected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mikrotik_settings', function (Blueprint $table) {
            $table->dropColumn('last_disconnected_at');
        });
    }
};
