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
            // Drop foreign key constraint first
            $table->dropForeign(['customer_id']);
            
            // Make customer_id nullable
            $table->foreignId('customer_id')->nullable()->change();
            
            // Re-add foreign key constraint with nullable
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ppp_secrets', function (Blueprint $table) {
            // Drop the nullable foreign key
            $table->dropForeign(['customer_id']);
            
            // Make customer_id not nullable again  
            $table->foreignId('customer_id')->nullable(false)->change();
            
            // Re-add original foreign key constraint
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }
};