<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('escrow_transactions', function (Blueprint $table) {
            $table->string('idempotency_key', 100)->nullable()->unique()->after('notes')
                  ->comment('Unique key to prevent duplicate processing');
            $table->index('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::table('escrow_transactions', function (Blueprint $table) {
            $table->dropColumn('idempotency_key');
        });
    }
};
