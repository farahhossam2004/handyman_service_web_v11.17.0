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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_elite')->default(0)->after('status');
            $table->integer('verification_level')->default(0)->after('is_elite');
            $table->integer('complaint_count')->default(0)->after('verification_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_elite', 'verification_level', 'complaint_count']);
        });
    }
};
