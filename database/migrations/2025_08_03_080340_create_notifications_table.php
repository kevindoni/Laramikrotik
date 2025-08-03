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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // payment, alert, system, etc
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // additional data like user_id, amount, etc
            $table->string('icon')->default('fas fa-info-circle');
            $table->string('color')->default('info'); // success, warning, danger, info
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // who should see this notification
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
