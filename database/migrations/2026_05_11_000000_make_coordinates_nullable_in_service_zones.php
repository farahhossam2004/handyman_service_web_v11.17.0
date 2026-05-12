<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('service_zones', function (Blueprint $table) {
            $table->json('coordinates')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('service_zones', function (Blueprint $table) {
            $table->json('coordinates')->nullable(false)->change();
        });
    }
};
