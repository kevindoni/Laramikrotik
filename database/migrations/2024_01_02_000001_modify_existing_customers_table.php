<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('ppp_profiles')) {
            Schema::create('ppp_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('rate_limit');
                $table->string('local_address')->nullable();
                $table->string('remote_address')->nullable();
                $table->string('dns_server')->nullable();
                $table->decimal('price', 10, 2);
                $table->boolean('only_one')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!Schema::hasColumn('customers', 'ppp_profile_id')) {
                    $table->unsignedBigInteger('ppp_profile_id')->nullable();
                    $table->foreign('ppp_profile_id')->references('id')->on('ppp_profiles')->onDelete('set null');
                }
            });
        }
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['ppp_profile_id']);
            $table->dropColumn('ppp_profile_id');
        });
        Schema::dropIfExists('ppp_profiles');
    }
};
