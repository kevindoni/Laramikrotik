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
        Schema::create('ppp_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('ppp_profile_id')->constrained()->onDelete('cascade');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('service')->default('pppoe');
            $table->string('remote_address')->nullable();
            $table->string('local_address')->nullable();
            $table->string('mikrotik_id')->nullable(); // ID di Mikrotik
            $table->boolean('is_active')->default(true);
            $table->string('comment')->nullable();
            $table->date('installation_date');
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppp_secrets');
    }
};