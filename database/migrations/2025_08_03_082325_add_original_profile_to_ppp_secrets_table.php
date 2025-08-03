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
        Schema::table('ppp_secrets', function (Blueprint $table) {
            $table->unsignedBigInteger('original_ppp_profile_id')->nullable()->after('ppp_profile_id');
            $table->foreign('original_ppp_profile_id')->references('id')->on('ppp_profiles')->onDelete('set null');
            $table->index('original_ppp_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ppp_secrets', function (Blueprint $table) {
            $table->dropForeign(['original_ppp_profile_id']);
            $table->dropIndex(['original_ppp_profile_id']);
            $table->dropColumn('original_ppp_profile_id');
        });
    }
};
