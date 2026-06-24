<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('national_id', 50)->nullable()->after('status');
            $table->string('national_id_image', 255)->nullable()->after('national_id');
            $table->enum('verification_status', ['pending_verification', 'approved', 'rejected'])->default('pending_verification')->after('national_id_image');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['national_id', 'national_id_image', 'verification_status']);
        });
    }
};
